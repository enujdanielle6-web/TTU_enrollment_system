<?php

declare(strict_types=1);

require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../includes/functions.php';
require_once __DIR__ . '/../../config/database.php';

requirePermission('scholarships.manage');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: scholarships.php');
    exit;
}

verifyCsrfToken();

$action = $_POST['action'] ?? '';

try {
    if ($action === 'create_scholarship') {
        $name = trim($_POST['name'] ?? '');
        $type = trim($_POST['discount_type'] ?? '');
        $value = (float)($_POST['discount_value'] ?? 0);
        $desc = trim($_POST['description'] ?? '');
        $reqs = trim($_POST['requirements'] ?? '');

        if ($name === '' || !in_array($type, ['percentage', 'fixed']) || $value <= 0) {
            throw new Exception('Invalid scholarship details provided.');
        }

        $stmt = $pdo->prepare('
            INSERT INTO scholarships (name, discount_type, discount_value, description, requirements) 
            VALUES (:name, :type, :value, :desc, :reqs)
        ');
        
        $stmt->execute([
            'name' => $name,
            'type' => $type,
            'value' => $value,
            'desc' => $desc,
            'reqs' => $reqs
        ]);

        logActivity((int)$_SESSION['user_id'], 'bi-award', 'Scholarship Created', "Created a new scholarship: " . $name);
        $_SESSION['success_msg'] = 'Scholarship created successfully.';
        header('Location: scholarships.php');
        exit;
    } 
    elseif ($action === 'update_scholarship') {
        $id = (int)($_POST['id'] ?? 0);
        $name = trim($_POST['name'] ?? '');
        $type = trim($_POST['discount_type'] ?? '');
        $value = (float)($_POST['discount_value'] ?? 0);
        $desc = trim($_POST['description'] ?? '');
        $reqs = trim($_POST['requirements'] ?? '');

        if ($id <= 0 || $name === '' || !in_array($type, ['percentage', 'fixed']) || $value <= 0) {
            throw new Exception('Missing or invalid required information.');
        }

        $stmt = $pdo->prepare('
            UPDATE scholarships 
            SET name = :name, 
                discount_type = :type, 
                discount_value = :value, 
                description = :desc,
                requirements = :reqs
            WHERE id = :id
        ');
        
        $stmt->execute([
            'name' => $name,
            'type' => $type,
            'value' => $value,
            'desc' => $desc,
            'reqs' => $reqs,
            'id' => $id
        ]);

        logActivity((int)$_SESSION['user_id'], 'bi-pencil', 'Scholarship Updated', "Updated details for scholarship: " . $name);
        $_SESSION['success_msg'] = 'Scholarship details updated successfully.';
        header('Location: scholarships.php');
        exit;
    }
    elseif ($action === 'toggle_scholarship') {
        $id = (int)($_POST['id'] ?? 0);
        $status = (int)($_POST['status'] ?? 0);

        if ($id <= 0) {
            throw new Exception('Invalid scholarship ID.');
        }

        $stmt = $pdo->prepare('UPDATE scholarships SET is_active = :status WHERE id = :id');
        $stmt->execute(['status' => $status, 'id' => $id]);

        $_SESSION['success_msg'] = 'Scholarship status updated successfully.';
        header('Location: scholarships.php');
        exit;
    }
    elseif ($action === 'process_application') {
        $appId = (int)($_POST['application_id'] ?? 0);
        $userId = (int)($_POST['user_id'] ?? 0);
        $scholarshipId = (int)($_POST['scholarship_id'] ?? 0);
        $assessmentId = (int)($_POST['assessment_id'] ?? 0);
        $status = $_POST['status'] ?? '';
        $feedback = trim($_POST['admin_feedback'] ?? '');

        if ($appId <= 0 || !in_array($status, ['pending', 'under_review', 'approved', 'rejected'])) {
            throw new Exception('Invalid application data or status.');
        }

        // Fetch Scholarship details to calculate discount if approving
        $scholStmt = $pdo->prepare('SELECT discount_type, discount_value, name FROM scholarships WHERE id = :id');
        $scholStmt->execute(['id' => $scholarshipId]);
        $scholarship = $scholStmt->fetch();

        if (!$scholarship) {
            throw new Exception('Scholarship not found.');
        }

        // Fetch assessment
        $assStmt = $pdo->prepare('SELECT * FROM student_assessments WHERE id = :id');
        $assStmt->execute(['id' => $assessmentId]);
        $assessment = $assStmt->fetch();

        if ($status === 'approved' && !$assessment) {
            throw new Exception('Cannot approve scholarship. The student does not have a generated fee assessment.');
        }

        // Begin Transaction
        $pdo->beginTransaction();

        try {
            // Fetch old application
            $oldAppStmt = $pdo->prepare('SELECT status, admin_feedback FROM scholarship_applications WHERE id = :id');
            $oldAppStmt->execute(['id' => $appId]);
            $oldApp = $oldAppStmt->fetch(PDO::FETCH_ASSOC);

            // Update scholarship application status
            $updAppStmt = $pdo->prepare('UPDATE scholarship_applications SET status = :status, admin_feedback = :feedback WHERE id = :id');
            $updAppStmt->execute([
                'status' => $status,
                'feedback' => $feedback !== '' ? $feedback : null,
                'id' => $appId
            ]);

            if ($assessment) {
                if ($status === 'approved') {
                    // Apply discount
                    // The scholarship should only cover the tuition fee
                    $targetAmount = (float)$assessment['tuition_fee'];
                    $discountValue = (float)$scholarship['discount_value'];
                    $discountAmount = 0;
                    
                    if ($scholarship['discount_type'] === 'percentage') {
                        $discountAmount = $targetAmount * ($discountValue / 100);
                    } else {
                        $discountAmount = $discountValue;
                    }
                    
                    // Cap discount at tuition amount
                    if ($discountAmount > $targetAmount) {
                        $discountAmount = $targetAmount;
                    }
                    
                    $total = (float)$assessment['total_amount'];
                    $netAmount = $total - $discountAmount;

                    $updAssStmt = $pdo->prepare('
                        UPDATE student_assessments 
                        SET scholarship_id = :schol_id, discount_amount = :discount, net_amount = :net 
                        WHERE id = :id
                    ');
                    $updAssStmt->execute([
                        'schol_id' => $scholarshipId,
                        'discount' => $discountAmount,
                        'net' => $netAmount,
                        'id' => $assessmentId
                    ]);
                } else {
                    // Remove discount if status was changed away from approved
                    // (Assuming a student can only have 1 scholarship applied, which is true based on the column)
                    // We only want to remove it if this specific scholarship was applied.
                    if ($assessment['scholarship_id'] == $scholarshipId) {
                        $updAssStmt = $pdo->prepare('
                            UPDATE student_assessments 
                            SET scholarship_id = NULL, discount_amount = 0.00, net_amount = total_amount 
                            WHERE id = :id
                        ');
                        $updAssStmt->execute(['id' => $assessmentId]);
                    }
                }
            }

            // Log activity for student
            $statusLabel = match($status) {
                'approved' => 'Approved',
                'rejected' => 'Rejected',
                'under_review' => 'Under Review',
                default => 'Pending'
            };
            
            $icon = match($status) {
                'approved' => 'bi-award-fill text-success',
                'rejected' => 'bi-x-circle-fill text-danger',
                'under_review' => 'bi-search text-info',
                default => 'bi-hourglass text-warning'
            };

            $logStmt = $pdo->prepare('INSERT INTO activity_logs (user_id, ip_address, affected_record, icon, title, description) VALUES (:user_id, :ip_address, :affected_record, :icon, :title, :description)');
            $logStmt->execute([
                'user_id' => $userId,
                'ip_address' => $_SERVER['REMOTE_ADDR'] ?? null,
                'affected_record' => "Scholarship Application #$appId",
                'icon' => $icon,
                'title' => 'Scholarship Application Update',
                'description' => 'Your application for "' . $scholarship['name'] . '" is now: ' . $statusLabel . '.' . ($feedback !== '' ? ' Feedback: ' . $feedback : '')
            ]);

            // Log activity for admin
            logActivity(
                (int)$_SESSION['user_id'],
                'bi-award-fill',
                'Scholarship ' . $statusLabel,
                "Updated status of Scholarship Application #$appId to $statusLabel.",
                "Scholarship Application #$appId",
                $oldApp,
                ['status' => $status, 'admin_feedback' => $feedback !== '' ? $feedback : null]
            );

            $pdo->commit();
            
            $_SESSION['success_msg'] = 'Scholarship application processed successfully.';
        } catch (Exception $e) {
            $pdo->rollBack();
            throw $e;
        }

        header('Location: scholarship_detail.php?id=' . $appId);
        exit;
    }
    else {
        throw new Exception('Invalid action requested.');
    }
} catch (Exception $e) {
    $_SESSION['error_msg'] = $e->getMessage();
    header('Location: scholarships.php');
    exit;
}

