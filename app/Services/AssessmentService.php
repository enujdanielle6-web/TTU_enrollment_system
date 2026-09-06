<?php

namespace App\Services;

use PDO;
use PDOException;

/**
 * Service responsible for managing student financial assessments,
 * fee calculation, immutable line-item snapshotting, and breakdown retrieval.
 */
class AssessmentService
{
    /**
     * Generates a new student assessment for an applicant based on their academic level,
     * grade level, strand/program, and enrolled/curriculum subjects.
     *
     * @param int $applicationId Application record ID
     * @param int $userId Student user ID
     * @param PDO $pdo Active database connection
     * @return int|null Generated or existing assessment ID, or null on failure
     */
    public static function generateAssessment(int $applicationId, int $userId, PDO $pdo): ?int
    {
        // 1. Check if assessment already exists for this application
        $checkStmt = $pdo->prepare('SELECT id FROM student_assessments WHERE application_id = :app_id LIMIT 1');
        $checkStmt->execute(['app_id' => $applicationId]);
        $existingId = $checkStmt->fetchColumn();
        if ($existingId) {
            return (int)$existingId;
        }

        // 2. Fetch applicant details to find matching fee template
        $appStmt = $pdo->prepare('SELECT academic_level, grade_level, strand, semester, section_id FROM applications WHERE id = :id LIMIT 1');
        $appStmt->execute(['id' => $applicationId]);
        $appData = $appStmt->fetch(PDO::FETCH_ASSOC);

        if (!$appData) {
            error_log("AssessmentService::generateAssessment failed: Application #{$applicationId} not found.");
            return null;
        }

        // 3. Find matching fee template
        $sql = 'SELECT * FROM fee_templates WHERE grade_level = :grade_level AND (strand = :strand OR (strand IS NULL AND :strand_null IS NULL))';
        $params = [
            'grade_level' => $appData['grade_level'],
            'strand' => $appData['strand'],
            'strand_null' => $appData['strand']
        ];

        if ($appData['academic_level'] === 'College') {
            $sql .= ' AND semester = :semester';
            $params['semester'] = $appData['semester'] ?? 'First';
        }
        $sql .= ' LIMIT 1';

        $ftStmt = $pdo->prepare($sql);
        $ftStmt->execute($params);
        $template = $ftStmt->fetch(PDO::FETCH_ASSOC);

        if (!$template) {
            error_log("AssessmentService::generateAssessment failed: No fee template found for App #{$applicationId} (Level: {$appData['academic_level']}, Grade: {$appData['grade_level']}, Strand: {$appData['strand']}).");
            return null;
        }

        $academicLevel = $appData['academic_level'];
        $feeTemplateId = (int)$template['id'];
        $tuitionFee = (float)$template['tuition_fee'];
        $isPerUnit = !empty($template['is_per_unit']);
        $totalUnits = 0;

        // 4. Calculate tuition if per-unit rate applies
        if ($isPerUnit) {
            if ($academicLevel === 'College') {
                $unitsStmt = $pdo->prepare('
                    SELECT SUM(s.units) 
                    FROM college_enrollments ce 
                    JOIN subjects s ON ce.subject_id = s.id 
                    WHERE ce.application_id = :app_id
                ');
                $unitsStmt->execute(['app_id' => $applicationId]);
                $totalUnits = (int)$unitsStmt->fetchColumn();

                if ($totalUnits === 0 && !empty($appData['section_id'])) {
                    $unitsStmt = $pdo->prepare('
                        SELECT SUM(s.units) 
                        FROM college_section_subjects css 
                        JOIN subjects s ON css.subject_id = s.id 
                        WHERE css.college_section_id = :sec_id
                    ');
                    $unitsStmt->execute(['sec_id' => $appData['section_id']]);
                    $totalUnits = (int)$unitsStmt->fetchColumn();
                }

                if ($totalUnits === 0) {
                    $unitsStmt = $pdo->prepare('
                        SELECT SUM(s.units)
                        FROM college_curriculum_subjects ccs
                        JOIN subjects s ON ccs.subject_id = s.id
                        JOIN college_curricula cc ON ccs.curriculum_id = cc.id
                        JOIN college_programs p ON cc.program_id = p.id
                        WHERE p.code = :strand AND ccs.year_level = :year_level AND ccs.semester = :semester
                    ');
                    $unitsStmt->execute([
                        'strand' => $appData['strand'],
                        'year_level' => $appData['grade_level'],
                        'semester' => $appData['semester'] ?? 'First'
                    ]);
                    $totalUnits = (int)$unitsStmt->fetchColumn();
                }
            } elseif ($academicLevel === 'Senior High School') {
                $unitsStmt = $pdo->prepare('
                    SELECT SUM(s.units) 
                    FROM shs_enrollments se 
                    JOIN subjects s ON se.subject_id = s.id 
                    WHERE se.application_id = :app_id
                ');
                $unitsStmt->execute(['app_id' => $applicationId]);
                $totalUnits = (int)$unitsStmt->fetchColumn();

                if ($totalUnits === 0 && !empty($appData['section_id'])) {
                    $unitsStmt = $pdo->prepare('
                        SELECT SUM(s.units) 
                        FROM shs_section_subjects ss 
                        JOIN subjects s ON ss.subject_id = s.id 
                        WHERE ss.shs_section_id = :sec_id
                    ');
                    $unitsStmt->execute(['sec_id' => $appData['section_id']]);
                    $totalUnits = (int)$unitsStmt->fetchColumn();
                }

                if ($totalUnits === 0) {
                    $unitsStmt = $pdo->prepare('
                        SELECT SUM(s.units)
                        FROM shs_curriculum_subjects scs
                        JOIN subjects s ON scs.subject_id = s.id
                        JOIN shs_curricula sc ON scs.curriculum_id = sc.id
                        JOIN shs_strands st ON sc.strand_id = st.id
                        WHERE st.code = :strand AND scs.grade_level = :grade_level AND scs.semester = :semester
                    ');
                    $unitsStmt->execute([
                        'strand' => $appData['strand'],
                        'grade_level' => $appData['grade_level'],
                        'semester' => $appData['semester'] ?? 'First'
                    ]);
                    $totalUnits = (int)$unitsStmt->fetchColumn();
                }
            }

            $tuitionFee = ($totalUnits > 0) ? ($totalUnits * (float)$template['tuition_fee']) : 0.0;
        }

        $miscFee = (float)$template['miscellaneous_fee'];
        $regFee = (float)$template['registration_fee'];
        $labFee = (float)$template['laboratory_fee'];
        $otherFees = (float)$template['other_fees'];
        $totalAmount = $tuitionFee + $miscFee + $regFee + $labFee + $otherFees;

        // 5. Insert student assessment record
        $insertAssStmt = $pdo->prepare('
            INSERT INTO student_assessments 
            (user_id, application_id, fee_template_id, tuition_fee, miscellaneous_fee, registration_fee, laboratory_fee, other_fees, total_amount, discount_amount, net_amount)
            VALUES 
            (:user_id, :app_id, :fee_id, :tuition, :misc, :reg, :lab, :other, :total_amount, 0, :net_amount)
        ');
        $insertAssStmt->execute([
            'user_id' => $userId,
            'app_id' => $applicationId,
            'fee_id' => $feeTemplateId,
            'tuition' => $tuitionFee,
            'misc' => $miscFee,
            'reg' => $regFee,
            'lab' => $labFee,
            'other' => $otherFees,
            'total_amount' => $totalAmount,
            'net_amount' => $totalAmount
        ]);

        $newAssessmentId = (int)$pdo->lastInsertId();

        // 6. Fetch subjects for immutable snapshot
        $subjectsForSnapshot = [];
        if ($academicLevel === 'College') {
            $snapSubStmt = $pdo->prepare('
                SELECT s.subject_code, s.subject_name, s.units 
                FROM college_enrollments ce 
                JOIN subjects s ON ce.subject_id = s.id 
                WHERE ce.application_id = :app_id
            ');
            $snapSubStmt->execute(['app_id' => $applicationId]);
            $subjectsForSnapshot = $snapSubStmt->fetchAll(PDO::FETCH_ASSOC);

            if (empty($subjectsForSnapshot) && !empty($appData['section_id'])) {
                $snapSubStmt = $pdo->prepare('
                    SELECT s.subject_code, s.subject_name, s.units 
                    FROM college_section_subjects css 
                    JOIN subjects s ON css.subject_id = s.id 
                    WHERE css.college_section_id = :sec_id
                ');
                $snapSubStmt->execute(['sec_id' => $appData['section_id']]);
                $subjectsForSnapshot = $snapSubStmt->fetchAll(PDO::FETCH_ASSOC);
            }

            if (empty($subjectsForSnapshot)) {
                $snapSubStmt = $pdo->prepare('
                    SELECT s.subject_code, s.subject_name, s.units
                    FROM college_curriculum_subjects ccs
                    JOIN subjects s ON ccs.subject_id = s.id
                    JOIN college_curricula cc ON ccs.curriculum_id = cc.id
                    JOIN college_programs p ON cc.program_id = p.id
                    WHERE p.code = :strand AND ccs.year_level = :year_level AND ccs.semester = :semester
                    ORDER BY ccs.display_order ASC
                ');
                $snapSubStmt->execute([
                    'strand' => $appData['strand'],
                    'year_level' => $appData['grade_level'],
                    'semester' => $appData['semester'] ?? 'First'
                ]);
                $subjectsForSnapshot = $snapSubStmt->fetchAll(PDO::FETCH_ASSOC);
            }
        } else {
            $snapSubStmt = $pdo->prepare('
                SELECT s.subject_code, s.subject_name, s.units 
                FROM shs_enrollments se 
                JOIN subjects s ON se.subject_id = s.id 
                WHERE se.application_id = :app_id
            ');
            $snapSubStmt->execute(['app_id' => $applicationId]);
            $subjectsForSnapshot = $snapSubStmt->fetchAll(PDO::FETCH_ASSOC);

            if (empty($subjectsForSnapshot) && !empty($appData['section_id'])) {
                $snapSubStmt = $pdo->prepare('
                    SELECT s.subject_code, s.subject_name, s.units 
                    FROM shs_section_subjects ss 
                    JOIN subjects s ON ss.subject_id = s.id 
                    WHERE ss.shs_section_id = :sec_id
                ');
                $snapSubStmt->execute(['sec_id' => $appData['section_id']]);
                $subjectsForSnapshot = $snapSubStmt->fetchAll(PDO::FETCH_ASSOC);
            }

            if (empty($subjectsForSnapshot)) {
                $snapSubStmt = $pdo->prepare('
                    SELECT s.subject_code, s.subject_name, s.units
                    FROM shs_curriculum_subjects scs
                    JOIN subjects s ON scs.subject_id = s.id
                    JOIN shs_curricula sc ON scs.curriculum_id = sc.id
                    JOIN shs_strands st ON sc.strand_id = st.id
                    WHERE st.code = :strand AND scs.grade_level = :grade_level AND scs.semester = :semester
                    ORDER BY scs.display_order ASC
                ');
                $snapSubStmt->execute([
                    'strand' => $appData['strand'],
                    'grade_level' => $appData['grade_level'],
                    'semester' => $appData['semester'] ?? 'First'
                ]);
                $subjectsForSnapshot = $snapSubStmt->fetchAll(PDO::FETCH_ASSOC);
            }
        }

        // 7. Create immutable snapshot in assessment_items
        if (function_exists('snapshotAssessmentItems')) {
            snapshotAssessmentItems(
                $pdo,
                $newAssessmentId,
                $applicationId,
                $template,
                $subjectsForSnapshot,
                $tuitionFee,
                $miscFee,
                $regFee,
                $labFee,
                $otherFees,
                0.00
            );
        }

        // 8. Recalculate assessment in case applicant already has scholarship
        self::recalculateAssessment($userId, $pdo);

        // 9. Log activity for the student
        $logDocStmt = $pdo->prepare('INSERT INTO activity_logs (user_id, icon, title, description) VALUES (:user_id, :icon, :title, :description)');
        $logDocStmt->execute([
            'user_id' => $userId,
            'icon' => 'bi-cash-stack text-success',
            'title' => "Financial Assessment Generated",
            'description' => "Your financial assessment has been generated and is ready for review."
        ]);

        return $newAssessmentId;
    }

    /**
     * Recalculates student assessment and updates discount snapshot.
     */
    public static function recalculateAssessment(int $userId, PDO $pdo): void
    {
        if (function_exists('recalculateStudentAssessment')) {
            recalculateStudentAssessment($userId, $pdo);
        }
    }

    /**
     * Retrieves the complete assessment breakdown, including payment history,
     * immutable assessment_items snapshots, enrolled subjects, and dynamic tuition auto-sync.
     *
     * @param PDO $pdo Database connection
     * @param int|null $assessmentId Specific assessment ID
     * @param int|null $userId User ID (fetches latest assessment)
     * @param int|null $applicationId Application ID
     * @return array|null Complete breakdown array or null if not found
     */
    public static function getAssessmentBreakdown(PDO $pdo, ?int $assessmentId = null, ?int $userId = null, ?int $applicationId = null): ?array
    {
        $baseSql = '
            SELECT sa.*, a.reference_number, a.academic_level, a.grade_level, a.strand, a.school_year, a.semester, a.section_id,
                   u.first_name, u.last_name, u.email,
                   s.name AS scholarship_name, ft.is_per_unit, ft.tuition_fee AS template_tuition_rate
            FROM student_assessments sa
            INNER JOIN users u ON sa.user_id = u.id
            INNER JOIN applications a ON sa.application_id = a.id
            LEFT JOIN scholarships s ON sa.scholarship_id = s.id
            LEFT JOIN fee_templates ft ON sa.fee_template_id = ft.id
        ';

        if ($assessmentId !== null) {
            $stmt = $pdo->prepare($baseSql . ' WHERE sa.id = :id LIMIT 1');
            $stmt->execute(['id' => $assessmentId]);
        } elseif ($userId !== null) {
            $stmt = $pdo->prepare($baseSql . ' WHERE sa.user_id = :user_id ORDER BY sa.created_at DESC LIMIT 1');
            $stmt->execute(['user_id' => $userId]);
        } elseif ($applicationId !== null) {
            $stmt = $pdo->prepare($baseSql . ' WHERE sa.application_id = :app_id LIMIT 1');
            $stmt->execute(['app_id' => $applicationId]);
        } else {
            return null;
        }

        $assessment = $stmt->fetch(PDO::FETCH_ASSOC);
        if (!$assessment) {
            return null;
        }

        $aid = (int)$assessment['id'];

        // 1. Fetch payment records with cashier name
        $payStmt = $pdo->prepare('
            SELECT pr.*, u.first_name as cashier_first, u.last_name as cashier_last
            FROM payment_records pr
            LEFT JOIN users u ON pr.cashier_id = u.id
            WHERE pr.assessment_id = :assessment_id
            ORDER BY pr.created_at DESC
        ');
        $payStmt->execute(['assessment_id' => $aid]);
        $payments = $payStmt->fetchAll(PDO::FETCH_ASSOC);

        // 2. Fetch immutable assessment items snapshot
        $itemStmt = $pdo->prepare('SELECT * FROM assessment_items WHERE assessment_id = :aid ORDER BY id ASC');
        $itemStmt->execute(['aid' => $aid]);
        $assessmentItems = $itemStmt->fetchAll(PDO::FETCH_ASSOC);

        // 3. Resolve enrolled subjects
        $enrolledSubjects = [];
        $tuitionSnapshots = array_filter($assessmentItems, fn($i) => $i['item_type'] === 'tuition' && !empty($i['units']));

        if (!empty($tuitionSnapshots)) {
            $enrolledSubjects = array_map(fn($i) => [
                'subject_code' => $i['item_code'],
                'subject_name' => $i['item_name'],
                'units' => (float)$i['units']
            ], array_values($tuitionSnapshots));
        } else {
            // Fallback for legacy assessments or un-snapshotted records
            if ($assessment['academic_level'] === 'College') {
                $subStmt = $pdo->prepare('
                    SELECT s.subject_code, s.subject_name, s.units 
                    FROM college_enrollments ce
                    JOIN subjects s ON ce.subject_id = s.id
                    WHERE ce.application_id = :app_id
                ');
                $subStmt->execute(['app_id' => $assessment['application_id']]);
                $enrolledSubjects = $subStmt->fetchAll(PDO::FETCH_ASSOC);

                if (empty($enrolledSubjects) && !empty($assessment['section_id'])) {
                    $secSubStmt = $pdo->prepare('
                        SELECT s.subject_code, s.subject_name, s.units
                        FROM college_section_subjects css
                        JOIN subjects s ON css.subject_id = s.id
                        WHERE css.college_section_id = :sec_id
                    ');
                    $secSubStmt->execute(['sec_id' => $assessment['section_id']]);
                    $enrolledSubjects = $secSubStmt->fetchAll(PDO::FETCH_ASSOC);
                }

                if (empty($enrolledSubjects)) {
                    $currSubStmt = $pdo->prepare('
                        SELECT s.subject_code, s.subject_name, s.units
                        FROM college_curriculum_subjects ccs
                        JOIN subjects s ON ccs.subject_id = s.id
                        JOIN college_curricula cc ON ccs.curriculum_id = cc.id
                        JOIN college_programs p ON cc.program_id = p.id
                        WHERE p.code = :strand AND ccs.year_level = :year_level AND ccs.semester = :semester
                        ORDER BY ccs.display_order ASC
                    ');
                    $currSubStmt->execute([
                        'strand' => $assessment['strand'],
                        'year_level' => $assessment['grade_level'],
                        'semester' => $assessment['semester'] ?? 'First'
                    ]);
                    $enrolledSubjects = $currSubStmt->fetchAll(PDO::FETCH_ASSOC);
                }
            } elseif ($assessment['academic_level'] === 'Senior High School') {
                $subStmt = $pdo->prepare('
                    SELECT s.subject_code, s.subject_name, s.units 
                    FROM shs_enrollments se
                    JOIN subjects s ON se.subject_id = s.id
                    WHERE se.application_id = :app_id
                ');
                $subStmt->execute(['app_id' => $assessment['application_id']]);
                $enrolledSubjects = $subStmt->fetchAll(PDO::FETCH_ASSOC);

                if (empty($enrolledSubjects) && !empty($assessment['section_id'])) {
                    $secSubStmt = $pdo->prepare('
                        SELECT s.subject_code, s.subject_name, s.units
                        FROM shs_section_subjects ss
                        JOIN subjects s ON ss.subject_id = s.id
                        WHERE ss.shs_section_id = :sec_id
                    ');
                    $secSubStmt->execute(['sec_id' => $assessment['section_id']]);
                    $enrolledSubjects = $secSubStmt->fetchAll(PDO::FETCH_ASSOC);
                }

                if (empty($enrolledSubjects)) {
                    $currSubStmt = $pdo->prepare('
                        SELECT s.subject_code, s.subject_name, s.units
                        FROM shs_curriculum_subjects scs
                        JOIN subjects s ON scs.subject_id = s.id
                        JOIN shs_curricula sc ON scs.curriculum_id = sc.id
                        JOIN shs_strands st ON sc.strand_id = st.id
                        WHERE st.code = :strand AND scs.grade_level = :grade_level AND scs.semester = :semester
                        ORDER BY scs.display_order ASC
                    ');
                    $currSubStmt->execute([
                        'strand' => $assessment['strand'],
                        'grade_level' => $assessment['grade_level'],
                        'semester' => $assessment['semester'] ?? 'First'
                    ]);
                    $enrolledSubjects = $currSubStmt->fetchAll(PDO::FETCH_ASSOC);
                }
            }
        }

        $totalUnits = (float)array_sum(array_column($enrolledSubjects, 'units'));

        // 4. Dynamic tuition auto-sync ONLY for open/unpaid assessments with 0 payments recorded
        $isUnpaid = ($assessment['payment_status'] === 'unpaid');
        $hasNoPayments = ((float)($assessment['total_paid'] ?? 0) == 0.0);

        if (!empty($assessment['is_per_unit']) && !empty($enrolledSubjects) && $isUnpaid && $hasNoPayments) {
            $unitRate = (float)($assessment['template_tuition_rate'] ?? 0);
            $expectedTuition = $totalUnits * $unitRate;

            if (abs($expectedTuition - (float)$assessment['tuition_fee']) > 0.01) {
                $diff = $expectedTuition - (float)$assessment['tuition_fee'];
                $newTotal = (float)$assessment['total_amount'] + $diff;
                $newNet = max(0, $newTotal - (float)$assessment['discount_amount']);

                $syncStmt = $pdo->prepare('UPDATE student_assessments SET tuition_fee = :tuition, total_amount = :total, net_amount = :net WHERE id = :id');
                $syncStmt->execute([
                    'tuition' => $expectedTuition,
                    'total' => $newTotal,
                    'net' => $newNet,
                    'id' => $aid
                ]);

                $assessment['tuition_fee'] = $expectedTuition;
                $assessment['total_amount'] = $newTotal;
                $assessment['net_amount'] = $newNet;

                // Refresh assessment_items if template is available
                if (!empty($assessment['fee_template_id']) && function_exists('snapshotAssessmentItems')) {
                    $tplStmt = $pdo->prepare('SELECT * FROM fee_templates WHERE id = :id');
                    $tplStmt->execute(['id' => $assessment['fee_template_id']]);
                    $tplData = $tplStmt->fetch(PDO::FETCH_ASSOC);
                    if ($tplData) {
                        snapshotAssessmentItems(
                            $pdo,
                            $aid,
                            (int)$assessment['application_id'],
                            $tplData,
                            $enrolledSubjects,
                            $expectedTuition,
                            (float)$assessment['miscellaneous_fee'],
                            (float)$assessment['registration_fee'],
                            (float)$assessment['laboratory_fee'],
                            (float)$assessment['other_fees'],
                            (float)$assessment['discount_amount']
                        );

                        // Reload assessment items
                        $itemStmt->execute(['aid' => $aid]);
                        $assessmentItems = $itemStmt->fetchAll(PDO::FETCH_ASSOC);
                    }
                }
            }
        }

        return [
            'assessment' => $assessment,
            'payments' => $payments,
            'assessment_items' => $assessmentItems,
            'enrolled_subjects' => $enrolledSubjects,
            'total_units' => $totalUnits,
        ];
    }
}
