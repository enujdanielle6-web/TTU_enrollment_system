<?php

declare(strict_types=1);

require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../config/database.php';

requireApplicantLogin();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: assessment.php');
    exit;
}

verifyCsrfToken();

$action = $_POST['action'] ?? '';
$userId = (int)$_SESSION['user_id'];

try {
    if ($action === 'submit_payment_proof') {
        $assessmentId = (int)($_POST['assessment_id'] ?? 0);
        $amount = (float)($_POST['amount'] ?? 0);
        $method = trim($_POST['payment_method'] ?? '');
        $refNo = trim($_POST['reference_number'] ?? '');

        if ($assessmentId <= 0 || $amount <= 0 || empty($method) || empty($refNo)) {
            throw new Exception("Invalid payment details provided.");
        }

        // Verify Assessment belongs to user
        $assStmt = $pdo->prepare('
            SELECT sa.* 
            FROM student_assessments sa
            INNER JOIN applications a ON sa.application_id = a.id
            WHERE sa.id = :id AND a.user_id = :user_id
        ');
        $assStmt->execute(['id' => $assessmentId, 'user_id' => $userId]);
        $assessment = $assStmt->fetch();

        if (!$assessment) {
            throw new Exception("Assessment not found or unauthorized.");
        }

        // Handle File Upload
        if (!isset($_FILES['proof_image']) || $_FILES['proof_image']['error'] !== UPLOAD_ERR_OK) {
            throw new Exception("Proof of payment screenshot is required.");
        }

        $fileTmpPath = $_FILES['proof_image']['tmp_name'];
        $fileName = $_FILES['proof_image']['name'];
        $fileSize = $_FILES['proof_image']['size'];
        $fileType = $_FILES['proof_image']['type'];

        // Max 2MB
        if ($fileSize > 2 * 1024 * 1024) {
            throw new Exception("The uploaded file exceeds the 2MB size limit.");
        }

        $allowedMimeTypes = ['image/jpeg', 'image/png'];
        if (!in_array($fileType, $allowedMimeTypes)) {
            throw new Exception("Invalid file format. Only JPG and PNG are allowed.");
        }

        $ext = pathinfo($fileName, PATHINFO_EXTENSION);
        $newFileName = 'proof_' . $userId . '_' . time() . '.' . $ext;
        $uploadDir = __DIR__ . '/../uploads/payments/';
        
        if (!is_dir($uploadDir)) {
            mkdir($uploadDir, 0777, true);
        }

        $destPath = $uploadDir . $newFileName;
        
        if (!move_uploaded_file($fileTmpPath, $destPath)) {
            throw new Exception("Failed to upload the file. Please try again.");
        }

        // Insert into payment_records as "pending"
        // Note: receipt_number and cashier_id are NULL until verified
        $stmt = $pdo->prepare('
            INSERT INTO payment_records (assessment_id, user_id, amount, payment_date, payment_method, reference_number, proof_image, status)
            VALUES (:ass_id, :user_id, :amount, CURDATE(), :method, :ref, :proof, "pending")
        ');
        
        $stmt->execute([
            'ass_id' => $assessmentId,
            'user_id' => $userId,
            'amount' => $amount,
            'method' => $method,
            'ref' => $refNo,
            'proof' => $newFileName
        ]);

        logActivity($userId, 'bi-cloud-upload', 'Payment Proof Uploaded', "Submitted proof of payment (Ref: $refNo) for ₱" . number_format($amount, 2));

        $_SESSION['success_msg'] = "Proof of payment submitted successfully! Please wait for the cashier's verification.";
        header('Location: assessment.php');
        exit;
    }
} catch (Exception $e) {
    $_SESSION['error_msg'] = $e->getMessage();
    header('Location: assessment.php');
    exit;
}

header('Location: assessment.php');
exit;
