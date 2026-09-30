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

            // 8. Enroll in section subjects (for regular students) or custom requested subjects (for irregular students)
            if (($app['student_type'] ?? '') !== 'Irregular' && !empty($app['section_id'])) {
                self::assignSectionSubjects($applicationId, (int)$app['section_id'], $academicLevel, $pdo);
            } elseif (($app['student_type'] ?? '') === 'Irregular') {
                $reqStmt = $pdo->prepare('SELECT subject_id, section_id FROM application_subject_requests WHERE application_id = :app_id');
                $reqStmt->execute(['app_id' => $applicationId]);
                $reqSubs = $reqStmt->fetchAll(PDO::FETCH_ASSOC);

                if (!empty($reqSubs)) {
                    $lmsService = new \App\Services\LmsService();
                    if ($academicLevel === 'College') {
                        $insCe = $pdo->prepare('INSERT INTO college_enrollments (application_id, subject_id, college_section_id, status) VALUES (:app_id, :sub_id, :sec_id, "enrolled") ON DUPLICATE KEY UPDATE college_section_id = VALUES(college_section_id), status = "enrolled", dropped_at = NULL');
                        $facStmt = $pdo->prepare('SELECT faculty_user_id FROM college_section_subjects WHERE college_section_id = :sec_id AND subject_id = :sub_id LIMIT 1');
                        foreach ($reqSubs as $rs) {
                            $secId = !empty($rs['section_id']) ? (int)$rs['section_id'] : ($app['section_id'] ?? null);
                            $subId = (int)$rs['subject_id'];
                            $insCe->execute([
                                'app_id' => $applicationId,
                                'sub_id' => $subId,
                                'sec_id' => $secId
                            ]);
                            if ($secId) {
                                $facStmt->execute(['sec_id' => $secId, 'sub_id' => $subId]);
                                $facId = $facStmt->fetchColumn() ?: null;
                                $lmsService->provisionCourseShell('College', $secId, $subId, $facId ? (int)$facId : null);
                            }
                        }
                    } else {
                        $insSe = $pdo->prepare('INSERT INTO shs_enrollments (application_id, subject_id, shs_section_id, status) VALUES (:app_id, :sub_id, :sec_id, "enrolled") ON DUPLICATE KEY UPDATE shs_section_id = VALUES(shs_section_id), status = "enrolled", dropped_at = NULL');
                        $facStmt = $pdo->prepare('SELECT faculty_user_id FROM shs_section_subjects WHERE shs_section_id = :sec_id AND subject_id = :sub_id LIMIT 1');
                        foreach ($reqSubs as $rs) {
                            $secId = !empty($rs['section_id']) ? (int)$rs['section_id'] : ($app['section_id'] ?? null);
                            $subId = (int)$rs['subject_id'];
                            $insSe->execute([
                                'app_id' => $applicationId,
                                'sub_id' => $subId,
                                'sec_id' => $secId
                            ]);
                            if ($secId) {
                                $facStmt->execute(['sec_id' => $secId, 'sub_id' => $subId]);
                                $facId = $facStmt->fetchColumn() ?: null;
                                $lmsService->provisionCourseShell('SHS', $secId, $subId, $facId ? (int)$facId : null);
                            }
                        }
                    }
                }
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
                    $credentialPassword = 'Use your registered account password';
                    sendStudentCredentialsEmail(
                        $app['email'],
                        $app['first_name'],
                        $ttuEmail,
                        $studentNumber,
                        $credentialPassword
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
     * Uses atomic UPSERT and explicitly provisions LMS course shells deterministically.
     */
    public static function assignSectionSubjects(int $applicationId, int $sectionId, string $academicLevel, PDO $pdo): int
    {
        $enrolledCount = 0;
        $lmsService = new \App\Services\LmsService();

        if ($academicLevel === 'College') {
            $secSubs = $pdo->prepare('SELECT subject_id, faculty_user_id FROM college_section_subjects WHERE college_section_id = :sec_id');
            $secSubs->execute(['sec_id' => $sectionId]);
            $subjects = $secSubs->fetchAll(PDO::FETCH_ASSOC);

            if (!empty($subjects)) {
                $insCe = $pdo->prepare('
                    INSERT INTO college_enrollments (application_id, subject_id, college_section_id, status)
                    VALUES (:app_id, :sub_id, :sec_id, "enrolled")
                    ON DUPLICATE KEY UPDATE college_section_id = VALUES(college_section_id), status = "enrolled", dropped_at = NULL
                ');

                foreach ($subjects as $s) {
                    $sId = (int)$s['subject_id'];
                    $fId = !empty($s['faculty_user_id']) ? (int)$s['faculty_user_id'] : null;

                    $insCe->execute([
                        'app_id' => $applicationId,
                        'sub_id' => $sId,
                        'sec_id' => $sectionId
                    ]);

                    // Deterministic LMS course shell provisioning
                    $lmsService->provisionCourseShell('College', $sectionId, $sId, $fId);
                    $enrolledCount++;
                }
            }
        } else {
            $secSubs = $pdo->prepare('SELECT subject_id, faculty_user_id FROM shs_section_subjects WHERE shs_section_id = :sec_id');
            $secSubs->execute(['sec_id' => $sectionId]);
            $subjects = $secSubs->fetchAll(PDO::FETCH_ASSOC);

            if (!empty($subjects)) {
                $insSe = $pdo->prepare('
                    INSERT INTO shs_enrollments (application_id, subject_id, shs_section_id, status)
                    VALUES (:app_id, :sub_id, :sec_id, "enrolled")
                    ON DUPLICATE KEY UPDATE shs_section_id = VALUES(shs_section_id), status = "enrolled", dropped_at = NULL
                ');

                foreach ($subjects as $s) {
                    $sId = (int)$s['subject_id'];
                    $fId = !empty($s['faculty_user_id']) ? (int)$s['faculty_user_id'] : null;

                    $insSe->execute([
                        'app_id' => $applicationId,
                        'sub_id' => $sId,
                        'sec_id' => $sectionId
                    ]);

                    $lmsService->provisionCourseShell('SHS', $sectionId, $sId, $fId);
                    $enrolledCount++;
                }
            }
        }

        return $enrolledCount;
    }

    /**
     * Executes an atomic section transfer for an enrolled student.
     * Keeps applications and enrollment tables (college_enrollments / shs_enrollments) synchronized.
     * Shifts active LMS course access to the new section while preserving historical activity records.
     */
    public static function transferSection(int $applicationId, int $newSectionId, ?int $performedByUserId = null, ?PDO $pdo = null): array
    {
        $pdo = $pdo ?? \App\Core\Database::getConnection();

        $appStmt = $pdo->prepare('
            SELECT a.id, a.user_id, a.academic_level, a.status, a.section_id, a.student_type,
                   u.first_name, u.last_name, u.student_number
            FROM applications a
            JOIN users u ON a.user_id = u.id
            WHERE a.id = :id
            LIMIT 1
        ');
        $appStmt->execute(['id' => $applicationId]);
        $app = $appStmt->fetch(PDO::FETCH_ASSOC);

        if (!$app) {
            return ['success' => false, 'error' => 'Application record not found.'];
        }

        if ($app['status'] !== 'enrolled') {
            return ['success' => false, 'error' => 'Section transfer can only be performed for officially enrolled students.'];
        }

        $oldSectionId = (int)($app['section_id'] ?? 0);
        if ($oldSectionId === $newSectionId) {
            return ['success' => true, 'message' => 'Student is already assigned to this section.'];
        }

        $level = $app['academic_level'] ?? 'College';
        $isCollege = ($level === 'College');

        // Verify target section exists and is active
        $secTable = $isCollege ? 'college_sections' : 'shs_sections';
        $secCheck = $pdo->prepare("SELECT id, section_code FROM {$secTable} WHERE id = :id AND status = 1");
        $secCheck->execute(['id' => $newSectionId]);
        $newSec = $secCheck->fetch(PDO::FETCH_ASSOC);

        if (!$newSec) {
            return ['success' => false, 'error' => 'Target section does not exist or is inactive.'];
        }

        $startedTransaction = false;
        if (!$pdo->inTransaction()) {
            $pdo->beginTransaction();
            $startedTransaction = true;
        }

        try {
            // 1. Update applications.section_id
            $updApp = $pdo->prepare('UPDATE applications SET section_id = :sec_id WHERE id = :id');
            $updApp->execute(['sec_id' => $newSectionId, 'id' => $applicationId]);

            $lmsService = new \App\Services\LmsService();

            if ($isCollege) {
                // Fetch section subjects of new section
                $secSubsStmt = $pdo->prepare('
                    SELECT subject_id, faculty_user_id 
                    FROM college_section_subjects 
                    WHERE college_section_id = :sec_id
                ');
                $secSubsStmt->execute(['sec_id' => $newSectionId]);
                $newSecSubjects = $secSubsStmt->fetchAll(PDO::FETCH_ASSOC);

                // Re-bind existing active enrollments to the new section
                $updCe = $pdo->prepare('
                    UPDATE college_enrollments 
                    SET college_section_id = :new_sec 
                    WHERE application_id = :app_id AND status = "enrolled"
                ');
                $updCe->execute(['new_sec' => $newSectionId, 'app_id' => $applicationId]);

                // Ensure all subjects in new section are enrolled and provisioned
                $insCe = $pdo->prepare('
                    INSERT INTO college_enrollments (application_id, subject_id, college_section_id, status)
                    VALUES (:app_id, :sub_id, :sec_id, "enrolled")
                    ON DUPLICATE KEY UPDATE college_section_id = VALUES(college_section_id), status = "enrolled", dropped_at = NULL
                ');

                foreach ($newSecSubjects as $nss) {
                    $sId = (int)$nss['subject_id'];
                    $fId = !empty($nss['faculty_user_id']) ? (int)$nss['faculty_user_id'] : null;

                    $insCe->execute([
                        'app_id' => $applicationId,
                        'sub_id' => $sId,
                        'sec_id' => $newSectionId
                    ]);

                    $lmsService->provisionCourseShell('College', $newSectionId, $sId, $fId);
                }
            } else {
                // SHS transfer
                $secSubsStmt = $pdo->prepare('
                    SELECT subject_id, faculty_user_id 
                    FROM shs_section_subjects 
                    WHERE shs_section_id = :sec_id
                ');
                $secSubsStmt->execute(['sec_id' => $newSectionId]);
                $newSecSubjects = $secSubsStmt->fetchAll(PDO::FETCH_ASSOC);

                $updSe = $pdo->prepare('
                    UPDATE shs_enrollments 
                    SET shs_section_id = :new_sec 
                    WHERE application_id = :app_id AND status = "enrolled"
                ');
                $updSe->execute(['new_sec' => $newSectionId, 'app_id' => $applicationId]);

                $insSe = $pdo->prepare('
                    INSERT INTO shs_enrollments (application_id, subject_id, shs_section_id, status)
                    VALUES (:app_id, :sub_id, :sec_id, "enrolled")
                    ON DUPLICATE KEY UPDATE shs_section_id = VALUES(shs_section_id), status = "enrolled", dropped_at = NULL
                ');

                foreach ($newSecSubjects as $nss) {
                    $sId = (int)$nss['subject_id'];
                    $fId = !empty($nss['faculty_user_id']) ? (int)$nss['faculty_user_id'] : null;

                    $insSe->execute([
                        'app_id' => $applicationId,
                        'sub_id' => $sId,
                        'sec_id' => $newSectionId
                    ]);

                    $lmsService->provisionCourseShell('SHS', $newSectionId, $sId, $fId);
                }
            }

            // Log activity for student
            $desc = "Section transfer: Reassigned from section #{$oldSectionId} to {$newSec['section_code']} (#{$newSectionId}). LMS courses updated.";
            $logStmt = $pdo->prepare('INSERT INTO activity_logs (user_id, icon, title, description) VALUES (:uid, :icon, :title, :desc)');
            $logStmt->execute([
                'uid' => (int)$app['user_id'],
                'icon' => 'bi-arrow-left-right text-primary',
                'title' => 'Section Transferred',
                'desc' => $desc
            ]);

            if ($performedByUserId && function_exists('logActivity')) {
                logActivity(
                    $performedByUserId,
                    'bi-arrow-left-right',
                    'Section Transfer',
                    $desc,
                    "Application #{$applicationId}",
                    ['section_id' => $oldSectionId],
                    ['section_id' => $newSectionId]
                );
            }

            if ($startedTransaction && $pdo->inTransaction()) {
                $pdo->commit();
            }

            return [
                'success' => true,
                'message' => "Successfully transferred to section {$newSec['section_code']} and synchronized LMS course access.",
                'new_section_code' => $newSec['section_code'],
                'new_section_id' => $newSectionId
            ];
        } catch (\Exception $e) {
            if ($startedTransaction && $pdo->inTransaction()) {
                $pdo->rollBack();
            }
            error_log('EnrollmentService::transferSection error: ' . $e->getMessage());
            return ['success' => false, 'error' => 'Section transfer failed: ' . $e->getMessage()];
        }
    }

    /**
     * Marks an enrolled subject as dropped or withdrawn.
     * Revokes active LMS course access while preserving historical assignments, submissions, and quiz attempts.
     */
    public static function dropSubject(int $applicationId, int $subjectId, string $academicLevel, string $status = 'dropped', ?int $performedByUserId = null, ?PDO $pdo = null): array
    {
        $pdo = $pdo ?? \App\Core\Database::getConnection();

        if (!in_array($status, ['dropped', 'withdrawn'], true)) {
            return ['success' => false, 'error' => 'Invalid status. Must be "dropped" or "withdrawn".'];
        }

        $table = ($academicLevel === 'College') ? 'college_enrollments' : 'shs_enrollments';

        $checkStmt = $pdo->prepare("SELECT id, status FROM {$table} WHERE application_id = :app_id AND subject_id = :sub_id LIMIT 1");
        $checkStmt->execute(['app_id' => $applicationId, 'sub_id' => $subjectId]);
        $enrollment = $checkStmt->fetch(PDO::FETCH_ASSOC);

        if (!$enrollment) {
            return ['success' => false, 'error' => 'Enrollment record for this subject not found.'];
        }

        if ($enrollment['status'] === $status) {
            return ['success' => true, 'message' => "Subject is already marked as {$status}."];
        }

        $startedTransaction = false;
        if (!$pdo->inTransaction()) {
            $pdo->beginTransaction();
            $startedTransaction = true;
        }

        try {
            $upd = $pdo->prepare("
                UPDATE {$table} 
                SET status = :status, dropped_at = NOW() 
                WHERE application_id = :app_id AND subject_id = :sub_id
            ");
            $upd->execute([
                'status' => $status,
                'app_id' => $applicationId,
                'sub_id' => $subjectId
            ]);

            // Fetch subject metadata
            $subStmt = $pdo->prepare('SELECT subject_code, subject_name FROM subjects WHERE id = ?');
            $subStmt->execute([$subjectId]);
            $sub = $subStmt->fetch(PDO::FETCH_ASSOC);
            $subCode = $sub['subject_code'] ?? "Subject #{$subjectId}";

            // Fetch student user_id
            $uStmt = $pdo->prepare('SELECT user_id FROM applications WHERE id = ?');
            $uStmt->execute([$applicationId]);
            $userId = (int)$uStmt->fetchColumn();

            if ($userId > 0) {
                $statusUpper = ucfirst($status);
                $logStmt = $pdo->prepare('INSERT INTO activity_logs (user_id, icon, title, description) VALUES (:uid, :icon, :title, :desc)');
                $logStmt->execute([
                    'uid' => $userId,
                    'icon' => 'bi-dash-circle text-warning',
                    'title' => "Subject {$statusUpper}",
                    'desc' => "Official enrollment in {$subCode} has been marked as {$status}. Active LMS access revoked."
                ]);
            }

            if ($performedByUserId && function_exists('logActivity')) {
                logActivity(
                    $performedByUserId,
                    'bi-dash-circle',
                    "Subject {$status}",
                    "Marked {$subCode} as {$status} for Application #{$applicationId}.",
                    "Application #{$applicationId}",
                    ['status' => $enrollment['status']],
                    ['status' => $status]
                );
            }

            if ($startedTransaction && $pdo->inTransaction()) {
                $pdo->commit();
            }

            return [
                'success' => true,
                'message' => "Subject {$subCode} successfully marked as {$status}. Active LMS access revoked while preserving historical work."
            ];
        } catch (\Exception $e) {
            if ($startedTransaction && $pdo->inTransaction()) {
                $pdo->rollBack();
            }
            error_log("EnrollmentService::dropSubject error: " . $e->getMessage());
            return ['success' => false, 'error' => "Failed to update subject status: " . $e->getMessage()];
        }
    }

    /**
     * Marks an enrolled subject as withdrawn.
     */
    public static function withdrawSubject(int $applicationId, int $subjectId, string $academicLevel, ?int $performedByUserId = null, ?PDO $pdo = null): array
    {
        return self::dropSubject($applicationId, $subjectId, $academicLevel, 'withdrawn', $performedByUserId, $pdo);
    }

    /**
     * Restores a dropped or withdrawn subject back to active enrollment.
     */
    public static function restoreSubject(int $applicationId, int $subjectId, string $academicLevel, ?int $performedByUserId = null, ?PDO $pdo = null): array
    {
        $pdo = $pdo ?? \App\Core\Database::getConnection();
        $table = ($academicLevel === 'College') ? 'college_enrollments' : 'shs_enrollments';

        $upd = $pdo->prepare("UPDATE {$table} SET status = 'enrolled', dropped_at = NULL WHERE application_id = :app_id AND subject_id = :sub_id");
        $upd->execute(['app_id' => $applicationId, 'sub_id' => $subjectId]);

        return ['success' => true, 'message' => 'Subject enrollment restored to active status.'];
    }
}
