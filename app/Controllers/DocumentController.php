<?php

namespace App\Controllers;

use App\Core\BaseController;
use App\Core\Request;
use App\Core\Response;
use App\Models\User;
use App\Models\ApplicationDocument;
use App\Core\Database;
use finfo;

class DocumentController extends BaseController
{
    public function index(Request $request, Response $response)
    {
        $userId = (int) $_SESSION['user_id'];
        $pdo = Database::getConnection();

        $appStmt = $pdo->prepare('SELECT * FROM applications WHERE user_id = :user_id LIMIT 1');
        $appStmt->execute(['user_id' => $userId]);
        $application = $appStmt->fetch();

        if (!$application) {
            $_SESSION['error_msg'] = 'Please complete the enrollment form first.';
            $response->redirect('/sia/applicant/dashboard.php');
            return;
        }

        $appId = (int) $application['id'];
        $academicLevel = $application['academic_level'];
        $studentType = $application['student_type'];
        $strand = $application['strand'];
        
        $requiredDocs = $this->getRequiredDocuments($academicLevel, $studentType, $strand);
        $uploadedDocs = ApplicationDocument::findByApplicationId($appId);

        $docStatus = [];
        foreach ($uploadedDocs as $doc) {
            $docStatus[$doc['document_name']] = [
                'status' => $doc['status'],
                'path' => $doc['file_path'],
                'date' => $doc['created_at'],
                'id' => $doc['id']
            ];
        }

        $allMandatorySubmitted = true;
        foreach ($requiredDocs as $doc) {
            if ($doc['required'] && !isset($docStatus[$doc['name']])) {
                $allMandatorySubmitted = false;
                break;
            }
            if ($doc['required'] && isset($docStatus[$doc['name']]) && $docStatus[$doc['name']]['status'] === 'rejected') {
                $allMandatorySubmitted = false;
                break;
            }
        }

        $successMsg = $_SESSION['doc_success'] ?? $_SESSION['success_msg'] ?? null;
        $errorMsg = $_SESSION['doc_error'] ?? $_SESSION['error_msg'] ?? null;
        unset($_SESSION['doc_success'], $_SESSION['doc_error'], $_SESSION['success_msg'], $_SESSION['error_msg']);

        $method = $application['document_submission_method'] ?? 'online';
        $isLocked = in_array($application['status'], ['under_review', 'approved', 'enrolled'], true);

        return $this->render('applicant/documents', [
            'application' => $application,
            'requiredDocs' => $requiredDocs,
            'documents' => $docStatus,
            'allMandatorySubmitted' => $allMandatorySubmitted,
            'method' => $method,
            'isLocked' => $isLocked,
            'successMsg' => $successMsg,
            'errorMsg' => $errorMsg
        ]);
    }

    public function upload(Request $request, Response $response)
    {
        $userId = (int) $_SESSION['user_id'];
        $isAjax = $request->isAjax();
        $pdo = Database::getConnection();

        $respond = function($success, $message, $extra = []) use ($response, $isAjax) {
            if ($isAjax) {
                $response->json(array_merge(['success' => $success, 'message' => $message], $extra));
            } else {
                if ($success) {
                    $_SESSION['doc_success'] = $message;
                } else {
                    $_SESSION['doc_error'] = $message;
                }
                $response->redirect('/sia/applicant/documents.php');
            }
            exit;
        };

        if (!$request->isPost()) {
            $respond(false, 'Invalid request method.');
        }

        $documentName = trim((string) $request->input('document_name', ''));
        $file = $_FILES['document_file'] ?? null;

        if ($documentName === '' || !$file || $file['error'] === UPLOAD_ERR_NO_FILE) {
            $respond(false, 'Please select a file to upload.');
        }

        if ($file['error'] !== UPLOAD_ERR_OK) {
            $respond(false, 'A server error occurred during file upload. Please try again.');
        }

        $maxSize = 5 * 1024 * 1024; // 5MB
        if ($file['size'] > $maxSize) {
            $respond(false, 'File exceeds the maximum limit of 5MB.');
        }

        $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
        $allowedExts = ['pdf', 'jpg', 'jpeg', 'png'];

        if (!in_array($ext, $allowedExts, true)) {
            $respond(false, 'Invalid file extension. Only PDF, JPG, JPEG, and PNG are allowed.');
        }

        $finfo = new finfo(FILEINFO_MIME_TYPE);
        $mimeType = $finfo->file($file['tmp_name']);

        $mimeMap = [
            'pdf' => 'application/pdf',
            'jpg' => 'image/jpeg',
            'jpeg' => 'image/jpeg',
            'png' => 'image/png'
        ];

        if (!isset($mimeMap[$ext]) || $mimeType !== $mimeMap[$ext]) {
            $respond(false, 'File content type mismatch. Uploaded file MIME type does not match its extension.');
        }

        try {
            $appStmt = $pdo->prepare('SELECT id FROM applications WHERE user_id = :user_id LIMIT 1');
            $appStmt->execute(['user_id' => $userId]);
            $app = $appStmt->fetch();

            if (!$app) {
                $respond(false, 'No active application found.');
            }
            
            $appId = (int) $app['id'];

            $safeDocName = preg_replace('/[^a-zA-Z0-9]+/', '_', strtolower($documentName));
            $uniq = bin2hex(random_bytes(8));
            $newFilename = sprintf('app_%d_%s_%s.%s', $appId, $safeDocName, $uniq, $ext);
            $uploadDir = __DIR__ . '/../../../uploads/documents/';
            
            if (!is_dir($uploadDir)) {
                mkdir($uploadDir, 0755, true);
            }

            $targetPath = $uploadDir . $newFilename;

            if (move_uploaded_file($file['tmp_name'], $targetPath)) {
                $existing = ApplicationDocument::findByDocumentName($appId, $documentName);
                if ($existing && !empty($existing['file_path'])) {
                    $oldPath = $uploadDir . basename($existing['file_path']);
                    if (file_exists($oldPath)) {
                        unlink($oldPath);
                    }
                }
                
                $docId = ApplicationDocument::saveUpload($appId, $documentName, $newFilename);
                User::logActivity($userId, 'Document Uploaded', "You successfully uploaded your {$documentName}.", 'bi-cloud-arrow-up');
                
                $respond(true, "{$documentName} uploaded successfully.", ['doc_id' => $docId]);
            } else {
                $respond(false, 'Failed to move uploaded file. Check directory permissions.');
            }
        } catch (\PDOException $e) {
            error_log('Upload DB Error: ' . $e->getMessage());
            $respond(false, 'A database error occurred.');
        }
    }

    public function workflow(Request $request, Response $response)
    {
        $userId = (int) $_SESSION['user_id'];
        $isAjax = $request->isAjax();
        $pdo = Database::getConnection();

        $respond = function($success, $message) use ($response, $isAjax) {
            if ($isAjax) {
                $response->json(['success' => $success, 'message' => $message]);
            } else {
                if ($success) {
                    $_SESSION['success_msg'] = $message;
                } else {
                    $_SESSION['error_msg'] = $message;
                }
                $response->redirect('/sia/applicant/documents.php');
            }
            exit;
        };

        if (!$request->isPost()) {
            $respond(false, 'Invalid request.');
        }

        $action = $request->input('action', '');
        if (!in_array($action, ['submit_documents', 'save_preference'], true)) {
            $respond(false, 'Invalid action.');
        }

        try {
            if ($action === 'save_preference') {
                $submissionMethod = $request->input('submission_method', '');
                if (!in_array($submissionMethod, ['online', 'on_campus'], true)) {
                    $respond(false, 'Invalid submission method.');
                }
                
                $appStmt = $pdo->prepare('SELECT id, status FROM applications WHERE user_id = :user_id LIMIT 1');
                $appStmt->execute(['user_id' => $userId]);
                $application = $appStmt->fetch();

                if (!$application) {
                    $respond(false, 'Application not found.');
                }
                
                $appId = (int) $application['id'];
                
                if ($submissionMethod === 'on_campus' && in_array($application['status'], ['pending', 'correction_required'])) {
                    $updStmt = $pdo->prepare('UPDATE applications SET document_submission_method = :method, status = "under_review" WHERE id = :id');
                    $updStmt->execute(['method' => $submissionMethod, 'id' => $appId]);
                    User::logActivity($userId, 'Submission Preference Updated', 'You selected On-Campus submission. Application is now under review.', 'bi-building text-primary');
                } else {
                    $updStmt = $pdo->prepare('UPDATE applications SET document_submission_method = :method WHERE id = :id');
                    $updStmt->execute(['method' => $submissionMethod, 'id' => $appId]);
                    User::logActivity($userId, 'Submission Preference Updated', 'You updated your document submission preference.', 'bi-gear text-primary');
                }

                $respond(true, 'Submission preference saved successfully.');
            }

            // Existing logic for submit_documents
            $appStmt = $pdo->prepare('SELECT id, status, academic_level, student_type, strand FROM applications WHERE user_id = :user_id LIMIT 1');
            $appStmt->execute(['user_id' => $userId]);
            $application = $appStmt->fetch();

            if (!$application) {
                $respond(false, 'Application not found.');
            }

            $appId = (int) $application['id'];
            $requiredDocs = $this->getRequiredDocuments($application['academic_level'], $application['student_type'], $application['strand']);
            $uploadedDocs = ApplicationDocument::findByApplicationId($appId);

            $docStatus = [];
            foreach ($uploadedDocs as $doc) {
                $docStatus[$doc['document_name']] = $doc['status'];
            }

            $allMandatorySubmitted = true;
            foreach ($requiredDocs as $doc) {
                if ($doc['required'] && !isset($docStatus[$doc['name']])) {
                    $allMandatorySubmitted = false;
                    break;
                }
                if ($doc['required'] && isset($docStatus[$doc['name']]) && $docStatus[$doc['name']] === 'rejected') {
                    $allMandatorySubmitted = false;
                    break;
                }
            }

            if (!$allMandatorySubmitted) {
                $respond(false, 'You must upload all mandatory documents before submitting.');
            }

            if (in_array($application['status'], ['pending', 'correction_required'])) {
                $updStmt = $pdo->prepare('UPDATE applications SET status = "under_review", document_submission_method = "online" WHERE id = :id');
                $updStmt->execute(['id' => $appId]);
                User::logActivity($userId, 'Documents Submitted', 'Your documents have been submitted for verification.', 'bi-check-all text-success');
            }

            $respond(true, 'Documents submitted successfully. Your application is now under review.');
        } catch (\PDOException $e) {
            error_log('Workflow Error: ' . $e->getMessage());
            $respond(false, 'A database error occurred.');
        }
    }

    public function viewDocument(Request $request, Response $response)
    {
        $userId = (int) $_SESSION['user_id'];
        $docId = (int) $request->input('id', 0);
        $pdo = Database::getConnection();

        if ($docId <= 0) {
            $response->setStatusCode(400);
            echo "Invalid document ID.";
            return;
        }

        try {
            $stmt = $pdo->prepare('
                SELECT d.file_path, a.user_id 
                FROM application_documents d
                JOIN applications a ON a.id = d.application_id
                WHERE d.id = :id
            ');
            $stmt->execute(['id' => $docId]);
            $document = $stmt->fetch();

            if (!$document || (int)$document['user_id'] !== $userId) {
                $response->setStatusCode(403);
                echo "Unauthorized access.";
                return;
            }

            $filepath = __DIR__ . '/../../../uploads/documents/' . basename($document['file_path']);

            if (!file_exists($filepath)) {
                $response->setStatusCode(404);
                echo "File not found on server.";
                return;
            }

            $finfo = new finfo(FILEINFO_MIME_TYPE);
            $mimeType = $finfo->file($filepath);

            header('Content-Type: ' . $mimeType);
            header('Content-Length: ' . filesize($filepath));
            header('Content-Disposition: inline; filename="' . basename($document['file_path']) . '"');
            
            readfile($filepath);
            exit;
        } catch (\PDOException $e) {
            $response->setStatusCode(500);
            echo "Database error.";
            return;
        }
    }

    private function getRequiredDocuments($academicLevel, $studentType, $strand)
    {
        $docs = [];
        if ($studentType === 'Transferee') {
            $docs[] = ['name' => 'Transcript of Records (TOR)', 'description' => 'True copy of grades or transcript from previous school.', 'required' => true];
            $docs[] = ['name' => 'Honorable Dismissal', 'description' => 'Transfer credential from your previous school.', 'required' => true];
        } else {
            $docs[] = ['name' => 'Form 138 (Report Card)', 'description' => 'Original copy of your recent report card.', 'required' => true];
        }
        $docs[] = ['name' => 'Certificate of Good Moral Character', 'description' => 'Issued by your previous school principal or guidance counselor.', 'required' => true];
        $docs[] = ['name' => 'PSA Birth Certificate', 'description' => 'Clear photocopy of PSA Birth Certificate.', 'required' => true];
        $docs[] = ['name' => '2x2 ID Picture', 'description' => 'Recent 2x2 ID picture with white background.', 'required' => true];
        
        return $docs;
    }
}



