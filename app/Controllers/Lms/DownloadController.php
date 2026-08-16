<?php
namespace App\Controllers\Lms;

use App\Core\BaseController;
use App\Core\Request;
use App\Core\Response;
use App\Services\LmsService;
use App\Core\Database;

class DownloadController extends BaseController
{
    public function downloadMaterial(Request $request, Response $response, string $id)
    {
        $materialId = filter_var($id, FILTER_VALIDATE_INT);
        if (!$materialId) {
            $this->forbidden($response);
            return;
        }

        $userId = $_SESSION['user_id'] ?? 0;
        $role = $_SESSION['role'] ?? '';

        if (!$userId) {
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
        
        if ($role === 'student' || $role === 'applicant') {
            $authorized = $lmsService->isStudentAuthorizedForCourse($userId, $material['lms_course_id']);
        } elseif ($role === 'faculty') {
            $authorized = $lmsService->isFacultyAuthorizedForCourse($userId, $material['lms_course_id']);
        } elseif ($role === 'admin' || $role === 'registrar') {
            // Admins have global access
            $authorized = true;
        }

        if (!$authorized) {
            $this->forbidden($response);
            return;
        }

        // Validate file path
        // The file_path in DB is relative to the app/uploads/lms/ directory, or absolute.
        // Let's assume file_path stores just the filename or relative path inside app/uploads/lms/
        $baseDir = realpath(__DIR__ . '/../../../app/uploads/lms');
        
        if (!$baseDir) {
            // Directory doesn't exist or is inaccessible
            error_log("LMS Upload directory not found.");
            $this->notFound($response);
            return;
        }

        // Construct absolute path
        // To be safe against path traversal stored in DB, we check if file exists and starts with baseDir
        $requestedPath = $baseDir . DIRECTORY_SEPARATOR . basename($material['file_path']);
        $realPath = realpath($requestedPath);

        if (!$realPath || strpos($realPath, $baseDir) !== 0 || !file_exists($realPath)) {
            error_log("Attempted to access invalid material file: " . $requestedPath);
            $this->notFound($response);
            return;
        }

        // File is safe to stream
        $this->streamFile($realPath, $material['file_name'], $material['mime_type']);
    }

    public function downloadSubmission(Request $request, Response $response, string $id)
    {
        $submissionId = filter_var($id, FILTER_VALIDATE_INT);
        if (!$submissionId) {
            $this->forbidden($response);
            return;
        }

        $userId = $_SESSION['user_id'] ?? 0;
        $role = $_SESSION['role'] ?? '';

        if (!$userId) {
            $this->forbidden($response);
            return;
        }

        $lmsService = new LmsService();
        $submission = $lmsService->getSubmissionById($submissionId);

        if (!$submission) {
            $this->notFound($response);
            return;
        }

        $assignment = $lmsService->getAssignment($submission['assignment_id']);
        if (!$assignment) {
            $this->notFound($response);
            return;
        }

        // Authorization check
        $authorized = false;
        
        if ($role === 'student' || $role === 'applicant') {
            // Student can only download their own submissions
            if ($submission['student_id'] == $userId) {
                $authorized = true;
            }
        } elseif ($role === 'faculty') {
            // Faculty can download submissions for their own courses
            $authorized = $lmsService->isFacultyAuthorizedForCourse($userId, $assignment['lms_course_id']);
        } elseif ($role === 'admin' || $role === 'registrar') {
            $authorized = true;
        }

        if (!$authorized) {
            $this->forbidden($response);
            return;
        }

        $baseDir = realpath(__DIR__ . '/../../../app/uploads/lms/submissions');
        
        if (!$baseDir) {
            error_log("LMS Submissions directory not found.");
            $this->notFound($response);
            return;
        }

        $requestedPath = $baseDir . DIRECTORY_SEPARATOR . basename($submission['file_path']);
        $realPath = realpath($requestedPath);

        if (!$realPath || strpos($realPath, $baseDir) !== 0 || !file_exists($realPath)) {
            error_log("Attempted to access invalid submission file: " . $requestedPath);
            $this->notFound($response);
            return;
        }

        $this->streamFile($realPath, $submission['file_name'], $submission['mime_type']);
    }

    private function streamFile(string $filePath, string $originalName, ?string $mimeType)
    {
        // Prevent execution
        if (pathinfo($filePath, PATHINFO_EXTENSION) === 'php') {
            die("Invalid file type.");
        }

        $mimeType = $mimeType ?: 'application/octet-stream';

        header('Content-Description: File Transfer');
        header('Content-Type: ' . $mimeType);
        header('Content-Disposition: attachment; filename="' . addslashes($originalName) . '"');
        header('Expires: 0');
        header('Cache-Control: must-revalidate, post-check=0, pre-check=0');
        header('Pragma: public');
        header('Content-Length: ' . filesize($filePath));

        // Clear output buffer to prevent corrupted downloads
        if (ob_get_length()) {
            ob_clean();
        }
        flush();
        
        readfile($filePath);
        exit;
    }

    private function forbidden(Response $response)
    {
        $response->setStatusCode(403);
        echo "403 Forbidden - You are not authorized to access this file.";
        exit;
    }

    private function notFound(Response $response)
    {
        $response->setStatusCode(404);
        echo "404 Not Found - The requested file does not exist.";
        exit;
    }
}
