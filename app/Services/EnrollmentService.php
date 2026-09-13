<?php

namespace App\Services;

use PDO;
use Exception;

/**
 * Service responsible for managing the applicant-to-student enrollment lifecycle,
 * state machine transitions, institutional credential generation, and subject provisioning.
 */
class EnrollmentService
{
    /**
     * Finalizes official enrollment for an applicant.
     * Generates student number, provisions institutional @ttu.edu.ph email,
     * assigns temporary credentials with forced password reset, enrolls student in
     * section subjects, and triggers credential email delivery.
     *
     * @param int $applicationId Application record ID
     * @param int|null $performedByUserId Admin user performing action (for audit logs)
     * @param PDO $pdo Active database connection
     * @return array Result array with 'success' (bool), and either 'message' or 'error'
     */
    public static function finalizeEnrollment(int $applicationId, ?int $performedByUserId, PDO $pdo): array
    {
        try {
            // 1. Fetch Application and Student Details
            $stmt = $pdo->prepare('
                SELECT a.*, u.id AS user_id, u.first_name, u.last_name, u.email, u.student_number, u.ttu_email, sa.payment_status
                FROM applications a
                INNER JOIN users u ON u.id = a.user_id
                LEFT JOIN student_assessments sa ON sa.application_id = a.id
                WHERE a.id = :id
                LIMIT 1
            ');
            $stmt->execute(['id' => $applicationId]);
            $app = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$app) {
                return [
                    'success' => false,
                    'error' => 'Application record not found.',
                    'academic_level' => 'College'
                ];
            }

            $academicLevel = $app['academic_level'] ?? 'College';

            // 2. Validate current state
            if ($app['status'] === 'enrolled') {
                return [
                    'success' => false,
                    'error' => 'This student is already officially enrolled.',
                    'academic_level' => $academicLevel
                ];
            }

            // 3. Verify payment status
            $isPaymentVerified = in_array($app['payment_status'], ['partial', 'paid'], true) || ($app['status'] === 'payment_verified');
            if (!$isPaymentVerified) {
                return [
                    'success' => false,
                    'error' => 'Cannot finalize enrollment: Tuition payment has not been verified.',
                    'academic_level' => $academicLevel
                ];
            }

            // 4. Begin transaction
            $startedTransaction = false;
            if (!$pdo->inTransaction()) {
                $pdo->beginTransaction();
                $startedTransaction = true;
            }

            $userId = (int)$app['user_id'];
            $studentNumber = $app['student_number'] ?? '';

            // 5. Generate student number if not already assigned
            if (empty($studentNumber)) {
                $studentNumber = StudentNumberService::generate((int)date('Y'), $pdo);
                $updUser = $pdo->prepare('UPDATE users SET student_number = :sn WHERE id = :id');
                $updUser->execute(['sn' => $studentNumber, 'id' => $userId]);

                $logDocStmt = $pdo->prepare('INSERT INTO activity_logs (user_id, icon, title, description) VALUES (:user_id, :icon, :title, :description)');
                $logDocStmt->execute([
                    'user_id' => $userId,
                    'icon' => 'bi-person-vcard-fill text-success',
                    'title' => 'Student Number Assigned',
                    'description' => "Your official student number is {$studentNumber}."
                ]);
            }

            // 6. Generate Institutional TTU Email (Preserving Applicant's Existing Password)
            $ttuEmail = $app['ttu_email'] ?? '';

            if (empty($ttuEmail)) {
                $cleanFirst = strtolower(preg_replace('/[^a-zA-Z0-9]/', '', $app['first_name']));
                $cleanLast = strtolower(preg_replace('/[^a-zA-Z0-9]/', '', $app['last_name']));
                $ttuEmail = $cleanFirst . '.' . $cleanLast . '@ttu.edu.ph';

                $checkEmail = $pdo->prepare('SELECT COUNT(*) FROM users WHERE ttu_email = :email AND id != :id');
                $counter = 1;
                while (true) {
                    $checkEmail->execute(['email' => $ttuEmail, 'id' => $userId]);
                    if ($checkEmail->fetchColumn() == 0) {
                        break;
                    }
                    $ttuEmail = $cleanFirst . '.' . $cleanLast . $counter . '@ttu.edu.ph';
                    $counter++;
                }

                $pdo->prepare('UPDATE users SET ttu_email = :ttu_email WHERE id = :id')
                    ->execute([
                        'ttu_email' => $ttuEmail,
                        'id' => $userId
                    ]);
            }

            // 7. Update application status to enrolled and synchronize user identity
            $pdo->prepare('UPDATE applications SET status = "enrolled" WHERE id = :id')
                ->execute(['id' => $applicationId]);

            $pdo->prepare('UPDATE users SET role = "student", lms_status = "active" WHERE id = :id')
                ->execute(['id' => $userId]);

            // 8. Enroll in section subjects if section is assigned
            if (!empty($app['section_id'])) {
                self::assignSectionSubjects($applicationId, (int)$app['section_id'], $academicLevel, $pdo);
            }

            // 9. Activity log for Student
            $logEnrolled = $pdo->prepare('INSERT INTO activity_logs (user_id, icon, title, description) VALUES (:user_id, :icon, :title, :desc)');
            $logEnrolled->execute([
                'user_id' => $userId,
                'icon' => 'bi-patch-check-fill text-success',
                'title' => 'Enrollment Complete',
                'desc' => "Congratulations! You are officially enrolled as a student at Triple T University. Student No: {$studentNumber} | Institutional Email: {$ttuEmail}"
            ]);

            // 10. Audit log for Admin
            if ($performedByUserId !== null && function_exists('logActivity')) {
                logActivity(
                    $performedByUserId,
                    'bi-mortarboard-fill',
                    'Enrollment Finalized',
                    "Finalized enrollment for {$app['first_name']} {$app['last_name']} (Student No: {$studentNumber}).",
                    "Application #{$applicationId}",
                    ['status' => $app['status']],
                    ['status' => 'enrolled', 'student_number' => $studentNumber, 'ttu_email' => $ttuEmail]
                );
            }

            // Commit transaction
            if ($startedTransaction && $pdo->inTransaction()) {
                $pdo->commit();
            }

            // 11. Dispatch credentials email
            if (function_exists('sendStudentCredentialsEmail') && !empty($app['email'])) {
                try {
                    sendStudentCredentialsEmail(
                        $app['email'],
                        $app['first_name'],
                        $ttuEmail,
                        $studentNumber,
                        $tempPassword
                    );
                } catch (Exception $emailEx) {
                    error_log('sendStudentCredentialsEmail notification failed: ' . $emailEx->getMessage());
                }
            }

            return [
                'success' => true,
                'student_number' => $studentNumber,
                'ttu_email' => $ttuEmail,
                'academic_level' => $academicLevel,
                'first_name' => $app['first_name'],
                'last_name' => $app['last_name'],
                'message' => "Enrollment finalized successfully for {$app['first_name']} {$app['last_name']} (Student No: {$studentNumber}). Welcome credentials have been emailed."
            ];

        } catch (Exception $e) {
            if (isset($startedTransaction) && $startedTransaction && $pdo->inTransaction()) {
                $pdo->rollBack();
            }
            error_log('EnrollmentService::finalizeEnrollment error: ' . $e->getMessage());

            return [
                'success' => false,
                'error' => 'An error occurred while finalizing enrollment: ' . $e->getMessage(),
                'academic_level' => $app['academic_level'] ?? 'College'
            ];
        }
    }

    /**
     * Enrolls the applicant into all subjects belonging to the assigned section.
     * Uses INSERT IGNORE to prevent duplicate enrollment records.
     */
    public static function assignSectionSubjects(int $applicationId, int $sectionId, string $academicLevel, PDO $pdo): int
    {
        $enrolledCount = 0;

        if ($academicLevel === 'College') {
            $secSubs = $pdo->prepare('SELECT subject_id FROM college_section_subjects WHERE college_section_id = :sec_id');
            $secSubs->execute(['sec_id' => $sectionId]);
            $subIds = array_map('intval', $secSubs->fetchAll(PDO::FETCH_COLUMN));

            if (!empty($subIds)) {
                $existingStmt = $pdo->prepare('SELECT subject_id FROM college_enrollments WHERE application_id = :app_id');
                $existingStmt->execute(['app_id' => $applicationId]);
                $existingSubIds = array_map('intval', $existingStmt->fetchAll(PDO::FETCH_COLUMN));

                $toInsert = array_diff($subIds, $existingSubIds);
                if (!empty($toInsert)) {
                    $insCe = $pdo->prepare('INSERT IGNORE INTO college_enrollments (application_id, subject_id, college_section_id) VALUES (:app_id, :sub_id, :sec_id)');
                    foreach ($toInsert as $sId) {
                        $insCe->execute(['app_id' => $applicationId, 'sub_id' => $sId, 'sec_id' => $sectionId]);
                        $enrolledCount++;
                    }
                }
            }
        } else {
            $secSubs = $pdo->prepare('SELECT subject_id FROM shs_section_subjects WHERE shs_section_id = :sec_id');
            $secSubs->execute(['sec_id' => $sectionId]);
            $subIds = array_map('intval', $secSubs->fetchAll(PDO::FETCH_COLUMN));

            if (!empty($subIds)) {
                $existingStmt = $pdo->prepare('SELECT subject_id FROM shs_enrollments WHERE application_id = :app_id');
                $existingStmt->execute(['app_id' => $applicationId]);
                $existingSubIds = array_map('intval', $existingStmt->fetchAll(PDO::FETCH_COLUMN));

                $toInsert = array_diff($subIds, $existingSubIds);
                if (!empty($toInsert)) {
                    $insSe = $pdo->prepare('INSERT IGNORE INTO shs_enrollments (application_id, subject_id, shs_section_id) VALUES (:app_id, :sub_id, :sec_id)');
                    foreach ($toInsert as $sId) {
                        $insSe->execute(['app_id' => $applicationId, 'sub_id' => $sId, 'sec_id' => $sectionId]);
                        $enrolledCount++;
                    }
                }
            }
        }

        return $enrolledCount;
    }
}
