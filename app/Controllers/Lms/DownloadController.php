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

        // Canonical & fallback material storage directories
        $candidatePaths = [
            dirname(__DIR__, 3) . '/storage/uploads/lms/materials/' . basename($material['file_path']),
            dirname(__DIR__, 3) . '/storage/uploads/lms/' . ltrim($material['file_path'], '/\\'),
            dirname(__DIR__, 3) . '/app/uploads/lms/' . basename($material['file_path']),
            dirname(__DIR__, 3) . '/app/uploads/lms/' . ltrim($material['file_path'], '/\\'),
            'C:/xampp/storage/lms_materials/' . basename($material['file_path'])
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
            error_log("LMS Material file not found on disk for material ID: {$materialId}");
            $this->notFound($response);
            return;
        }

        // File is safe to stream
        $this->streamFile($resolvedPath, $material['file_name'], $material['mime_type']);
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
