<?php
namespace App\Controllers\Lms;

use App\Core\BaseController;
use App\Core\Request;
use App\Core\Response;
use App\Services\LmsService;
use App\Core\Database;

class DownloadController extends BaseController
{
    public function downloadMaterial(Request $request, Response $response, string $id, bool $inline = false)
    {
        $materialId = filter_var($id, FILTER_VALIDATE_INT);
        if (!$materialId) {
            $this->forbidden($response);
            return;
        }

        $userId = (int)($_SESSION['user_id'] ?? 0);
        $role = $_SESSION['user_role'] ?? $_SESSION['lms_role'] ?? '';

        if (!$userId || empty($role)) {
            $this->forbidden($response);
            return;
        }

        $lmsService = new LmsService();
        $material = $lmsService->getMaterial($materialId);

        if (!$material) {
            $this->notFound($response);
            return;
        }

        // Authorization check based on role
        $authorized = false;
        
        if ($role === 'student') {
            $authorized = $lmsService->isStudentAuthorizedForCourse($userId, (int)$material['lms_course_id']);
        } elseif ($role === 'faculty') {
            $authorized = $lmsService->isFacultyAuthorizedForCourse($userId, (int)$material['lms_course_id']);
        } elseif (in_array($role, ['admin', 'superadmin', 'registrar'], true)) {
            // Institutional administrative oversight access
            $authorized = true;
        }

        if (!$authorized) {
            $this->forbidden($response);
            return;
        }

        $resolvedPath = $lmsService->resolveMaterialPath($material);
        if (!$resolvedPath) {
            error_log("LMS Material file not found on disk for material ID: {$materialId}");
            $this->notFound($response);
            return;
        }

        // A lesson type the preview window cannot show is finished once the student downloads it
        if ($role === 'student' && !$inline && LmsService::lessonKind($material) === 'other') {
            try {
                (new \App\Services\LmsProgressService())->recordLessonProgress($userId, $materialId, 100);
            } catch (\Throwable $e) {
                error_log('Lesson progress unavailable: ' . $e->getMessage());
            }
        }

        // PDFs, images and audio/video open inside the lesson preview window; everything else downloads
        $showInline = $inline && in_array(LmsService::lessonKind($material), ['pdf', 'image', 'media'], true);
        $this->streamFile($resolvedPath, $material['file_name'], $material['mime_type'], $showInline);
    }

    /**
     * Same file and access rules as downloadMaterial, but served for display in the
     * lesson preview window (Content-Disposition: inline) where the type allows it.
     */
    public function viewMaterial(Request $request, Response $response, string $id)
    {
        $this->downloadMaterial($request, $response, $id, true);
    }

    public function downloadSubmission(Request $request, Response $response, string $id)
    {
        $submissionId = filter_var($id, FILTER_VALIDATE_INT);
        if (!$submissionId) {
            $this->forbidden($response);
            return;
        }

        $userId = (int)($_SESSION['user_id'] ?? 0);
        $role = $_SESSION['user_role'] ?? $_SESSION['lms_role'] ?? '';

        if (!$userId || empty($role)) {
            $this->forbidden($response);
            return;
        }

        $lmsService = new LmsService();
        $submission = $lmsService->getSubmissionById($submissionId);

        if (!$submission) {
            $this->notFound($response);
            return;
        }

        $assignment = $lmsService->getAssignment((int)$submission['assignment_id']);
        if (!$assignment) {
            $this->notFound($response);
            return;
        }

        // Authorization check
        $authorized = false;
        
        if ($role === 'student') {
            // Student can only download their own submissions
            if ((int)$submission['student_id'] === $userId) {
                $authorized = true;
            }
        } elseif ($role === 'faculty') {
            // Faculty can download submissions for their own courses
            $authorized = $lmsService->isFacultyAuthorizedForCourse($userId, (int)$assignment['lms_course_id']);
        } elseif (in_array($role, ['admin', 'superadmin', 'registrar'], true)) {
            $authorized = true;
        }

        if (!$authorized) {
            $this->forbidden($response);
            return;
        }

        // Canonical & fallback submission storage directories
        $candidatePaths = [
            dirname(__DIR__, 3) . '/storage/uploads/lms/submissions/' . basename($submission['file_path']),
            dirname(__DIR__, 3) . '/app/uploads/lms/submissions/' . basename($submission['file_path']),
            dirname(__DIR__, 3) . '/app/uploads/lms/submissions/' . ltrim($submission['file_path'], '/\\')
        ];

        $resolvedPath = null;
        foreach ($candidatePaths as $candidate) {
            if (file_exists($candidate)) {
                $real = realpath($candidate);
                if ($real && file_exists($real)) {
                    $resolvedPath = $real;
                    break;
                }
            }
        }

        if (!$resolvedPath) {
            error_log("LMS Submission file not found on disk for submission ID: {$submissionId}");
            $this->notFound($response);
            return;
        }

        $this->streamFile($resolvedPath, $submission['file_name'], $submission['mime_type']);
    }

    /**
     * MIME types for common course files, keyed by extension. Used when the stored
     * MIME type is missing or generic (fileinfo often reports Office files as zip).
     */
    private const MIME_BY_EXTENSION = [
        'pdf'  => 'application/pdf',
        'ppt'  => 'application/vnd.ms-powerpoint',
        'pptx' => 'application/vnd.openxmlformats-officedocument.presentationml.presentation',
        'pps'  => 'application/vnd.ms-powerpoint',
        'ppsx' => 'application/vnd.openxmlformats-officedocument.presentationml.slideshow',
        'doc'  => 'application/msword',
        'docx' => 'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
        'xls'  => 'application/vnd.ms-excel',
        'xlsx' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        'csv'  => 'text/csv',
        'txt'  => 'text/plain',
        'zip'  => 'application/zip',
        'png'  => 'image/png',
        'jpg'  => 'image/jpeg',
        'jpeg' => 'image/jpeg',
        'gif'  => 'image/gif',
        'mp4'  => 'video/mp4',
        'mp3'  => 'audio/mpeg',
    ];

    private function streamFile(string $filePath, string $originalName, ?string $mimeType, bool $inline = false)
    {
        $ext = strtolower(pathinfo($filePath, PATHINFO_EXTENSION));

        // Prevent execution
        if ($ext === 'php') {
            die("Invalid file type.");
        }

        // The stored name is the display title (e.g. "Week 1 Lecture"), which usually has
        // no extension. Append the stored file's extension so the browser saves a usable file.
        $downloadName = str_replace(["\r", "\n", '"', '/', '\\'], '', trim($originalName));
        if ($downloadName === '') {
            $downloadName = 'download';
        }
        if ($ext !== '' && strtolower(pathinfo($downloadName, PATHINFO_EXTENSION)) !== $ext) {
            $downloadName .= '.' . $ext;
        }

        $genericTypes = ['', 'application/octet-stream', 'application/zip', 'application/x-zip-compressed', 'application/vnd.ms-office', 'application/cdfv2'];
        if (in_array(strtolower((string)$mimeType), $genericTypes, true) && isset(self::MIME_BY_EXTENSION[$ext])) {
            $mimeType = self::MIME_BY_EXTENSION[$ext];
        }
        $mimeType = $mimeType ?: 'application/octet-stream';

        // Drop anything already buffered (stray whitespace, notices) so it isn't prepended to the file
        while (ob_get_level() > 0) {
            ob_end_clean();
        }

        $asciiName = preg_replace('/[^\x20-\x7E]/', '_', $downloadName);

        header('Content-Description: File Transfer');
        header('Content-Type: ' . $mimeType);
        header('Content-Disposition: ' . ($inline ? 'inline' : 'attachment') . '; filename="' . $asciiName . '"; filename*=UTF-8\'\'' . rawurlencode($downloadName));
        header('X-Content-Type-Options: nosniff');
        header('Expires: 0');
        header('Cache-Control: must-revalidate, post-check=0, pre-check=0');
        header('Pragma: public');
        header('Content-Length: ' . filesize($filePath));

        readfile($filePath);
        exit;
    }

    protected function forbidden(?Response $response = null, string $message = '403 Forbidden - You are not authorized to access this file.'): void
    {
        $res = $response ?? new Response();
        $res->setStatusCode(403);
        echo $message;
        exit;
    }

    protected function notFound(?Response $response = null, string $message = '404 Not Found - The requested file does not exist.'): void
    {
        $res = $response ?? new Response();
        $res->setStatusCode(404);
        echo $message;
        exit;
    }
}
