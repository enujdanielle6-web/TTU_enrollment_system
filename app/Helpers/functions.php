<?php

declare(strict_types=1);

/**
 * Escapes HTML characters in a string to prevent XSS.
 * 
 * @param mixed $string
 * @return string
 */
function esc($string): string
{
    if ($string === null) {
        return '';
    }
    return htmlspecialchars((string)$string, ENT_QUOTES, 'UTF-8');
}
function formatApplicationStatus(string $status): string
{
    $labels = [
        'pending' => 'Pending',
        'under_review' => 'Under Review',
        'correction_required' => 'Correction Required',
        'approved' => 'Approved',
        'rejected' => 'Rejected',
        'enrolled' => 'Enrolled',
    ];

    return $labels[$status] ?? ucfirst(str_replace('_', ' ', $status));
}

function getApplicationStatusBadgeClass(string $status): string
{
    $classes = [
        'pending' => 'bg-warning text-dark',
        'under_review' => 'bg-info text-dark',
        'correction_required' => 'bg-warning text-dark',
        'approved' => 'bg-success',
        'rejected' => 'bg-danger',
        'enrolled' => 'bg-success',
    ];

    return $classes[$status] ?? 'bg-secondary';
}

function getApplicationStatusMessage(string $status): string
{
    $messages = [
        'pending' => 'Your application has been submitted and is waiting for review.',
        'under_review' => 'The admissions team is currently reviewing your application.',
        'correction_required' => 'Please update your application based on the admin feedback.',
        'approved' => 'Congratulations! Your application has been approved.',
        'rejected' => 'Your application was not approved. Contact the admissions office for details.',
        'enrolled' => 'You are officially enrolled. Welcome to the school!',
    ];

    return $messages[$status] ?? 'Your application status has been updated.';
}

/**
 * @return array<int, array{key: string, label: string, description: string, state: string}>
 */
function getApplicationTimelineSteps(string $status, string $docMethod = 'online', array $timestamps = [], bool $hasUploadedDocs = false): array
{
    $steps = [
        [
            'key' => 'created',
            'label' => 'Account Created',
            'description' => 'Your portal account was successfully created.',
            'state' => 'completed',
            'timestamp' => $timestamps['created'] ?? null,
        ],
        [
            'key' => 'submitted',
            'label' => 'Application Submitted',
            'description' => 'Your enrollment form was submitted and received.',
            'state' => 'pending',
            'timestamp' => $timestamps['submitted'] ?? null,
        ],
        [
            'key' => 'documents',
            'label' => $docMethod === 'on_campus' ? 'On-Campus Verification' : 'Documents Upload',
            'description' => $docMethod === 'on_campus' ? 'Physical document verification required at the Admissions Office before approval.' : 'Required academic documents uploaded (Verification pending).',
            'state' => 'pending',
            'timestamp' => $timestamps['documents'] ?? null,
        ],
        [
            'key' => 'review',
            'label' => 'Under Review',
            'description' => 'The admissions office is verifying your details.',
            'state' => 'pending',
            'timestamp' => $timestamps['review'] ?? null,
        ],
    ];

    if ($status === 'correction_required' || !empty($timestamps['correction'])) {
        $steps[] = [
            'key' => 'correction',
            'label' => 'Correction Required',
            'description' => 'Please update your details according to admin feedback.',
            'state' => 'active',
            'timestamp' => $timestamps['correction'] ?? null,
        ];
    }

    $steps[] = [
        'key' => 'approved',
        'label' => 'Application Approved',
        'description' => 'Your enrollment application has been verified and approved.',
        'state' => 'pending',
        'timestamp' => $timestamps['approved'] ?? null,
    ];

    $steps[] = [
        'key' => 'health_info',
        'label' => 'Health Information',
        'description' => 'Submit your health information.',
        'state' => 'pending',
        'timestamp' => null,
    ];

    $steps[] = [
        'key' => 'medical_clearance',
        'label' => 'Medical Clearance',
        'description' => 'Clinic verification of your medical clearance.',
        'state' => 'pending',
        'timestamp' => null,
    ];

    $steps[] = [
        'key' => 'scholarship',
        'label' => 'Scholarship (Optional)',
        'description' => 'Apply for academic or financial scholarships.',
        'state' => 'pending',
        'timestamp' => null,
    ];

    $steps[] = [
        'key' => 'cashier',
        'label' => 'Cashier / Assessment',
        'description' => 'Settle your enrollment fees.',
        'state' => 'pending',
        'timestamp' => null,
    ];

    $steps[] = [
        'key' => 'enrolled',
        'label' => 'Enrollment Complete',
        'description' => 'You are officially enrolled as a student.',
        'state' => 'pending',
        'timestamp' => $timestamps['enrolled'] ?? null,
    ];

    // Map database application status to timeline step states
    if ($status === 'pending') {
        $steps[1]['state'] = 'completed'; // Submitted
        if ($docMethod === 'on_campus') {
            $steps[2]['state'] = 'active'; // Waiting for on-campus verification
            $steps[3]['state'] = 'pending';
        } else {
            if ($hasUploadedDocs) {
                $steps[2]['state'] = 'completed'; // Documents uploaded
                $steps[3]['state'] = 'active';    // Under review is active
            } else {
                $steps[2]['state'] = 'active';    // Waiting for documents
                $steps[3]['state'] = 'pending';   // Under review pending
            }
        }
    } elseif ($status === 'under_review') {
        $steps[1]['state'] = 'completed';
        $steps[2]['state'] = 'completed';
        $steps[3]['state'] = 'active';
    } elseif ($status === 'correction_required') {
        $steps[1]['state'] = 'completed';
        $steps[2]['state'] = 'completed';
        $steps[3]['state'] = 'completed';
        // 'correction' is active by default
    } elseif ($status === 'approved') {
        $steps[1]['state'] = 'completed';
        $steps[2]['state'] = 'completed';
        $steps[3]['state'] = 'completed';
        foreach ($steps as &$step) {
            if ($step['key'] === 'approved') {
                $step['state'] = 'completed';
            }
            // Logic for health/medical could be refined based on DB if we pass health status here,
            // but since we only have application status here, we'll mark the next logical step active.
            if ($step['key'] === 'health_info') {
                $step['state'] = 'active'; // Or we let applicant/dashboard.php refine this based on health_records
            }
        }
        unset($step);
    } elseif ($status === 'rejected') {
        $steps[1]['state'] = 'completed';
        $steps[2]['state'] = 'completed';
        $steps[3]['state'] = 'completed';
        foreach ($steps as &$step) {
            if ($step['key'] === 'approved') {
                $step['label'] = 'Application Rejected';
                $step['description'] = 'Your enrollment application was not approved.';
                $step['state'] = 'rejected';
            }
        }
        unset($step);
    } elseif ($status === 'enrolled') {
        foreach ($steps as &$step) {
            if ($step['key'] !== 'correction') {
                $step['state'] = 'completed';
            }
        }
        unset($step);
    }

    return $steps;
}

/**
 * Returns the contextual Quick Action button for a timeline step.
 *
 * @param array $step
 * @param array|null $application
 * @param string|null $healthStatus
 * @return array{url: string, label: string, icon: string, class: string}|null
 */
function getStepAction(array $step, ?array $application = null, ?string $healthStatus = null): ?array
{
    $key = $step['key'] ?? '';
    $state = $step['state'] ?? 'pending';

    if ($state === 'active') {
        return match ($key) {
            'submitted' => [
                'url' => 'enroll.php',
                'label' => 'Start Enrollment',
                'icon' => 'bi-pencil-square',
                'class' => 'btn-primary'
            ],
            'documents' => [
                'url' => 'documents.php',
                'label' => ($application['document_submission_method'] ?? '') === 'on_campus' ? 'View Campus Guide' : 'Upload Documents',
                'icon' => ($application['document_submission_method'] ?? '') === 'on_campus' ? 'bi-building' : 'bi-cloud-arrow-up-fill',
                'class' => 'btn-primary'
            ],
            'review' => [
                'url' => 'status.php',
                'label' => 'Track Status',
                'icon' => 'bi-search',
                'class' => 'btn-outline-primary'
            ],
            'correction' => [
                'url' => 'enroll.php',
                'label' => 'Update Application',
                'icon' => 'bi-pencil-fill',
                'class' => 'btn-danger'
            ],
            'approved' => [
                'url' => 'print_slip.php',
                'label' => 'View Slip',
                'icon' => 'bi-file-earmark-text',
                'class' => 'btn-success'
            ],
            'health_info' => [
                'url' => 'health_info.php',
                'label' => 'Submit Health Info',
                'icon' => 'bi-heart-pulse-fill',
                'class' => 'btn-primary'
            ],
            'medical_clearance' => [
                'url' => 'health_info.php',
                'label' => 'View Clearance',
                'icon' => 'bi-file-medical-fill',
                'class' => 'btn-outline-primary'
            ],
            'scholarship' => [
                'url' => 'scholarships.php',
                'label' => 'Apply Scholarship',
                'icon' => 'bi-mortarboard-fill',
                'class' => 'btn-primary'
            ],
            'cashier' => [
                'url' => 'assessment.php',
                'label' => 'Pay Assessment',
                'icon' => 'bi-credit-card-2-front-fill',
                'class' => 'btn-primary'
            ],
            'enrolled' => [
                'url' => 'print_slip.php',
                'label' => 'Print Summary',
                'icon' => 'bi-printer-fill',
                'class' => 'btn-success'
            ],
            default => null
        };
    }

    if ($state === 'completed') {
        return match ($key) {
            'approved' => [
                'url' => 'print_slip.php',
                'label' => 'View Slip',
                'icon' => 'bi-file-earmark-text',
                'class' => 'btn-outline-success'
            ],
            'cashier' => [
                'url' => 'assessment.php',
                'label' => 'Assessment',
                'icon' => 'bi-receipt',
                'class' => 'btn-outline-primary'
            ],
            'enrolled' => [
                'url' => 'print_slip.php',
                'label' => 'Print Summary',
                'icon' => 'bi-printer',
                'class' => 'btn-outline-success'
            ],
            default => null
        };
    }

    return null;
}

function formatDisplayDate(?string $date): string
{
    if ($date === null || $date === '') {
        return '—';
    }

    $timestamp = strtotime($date);

    if ($timestamp === false) {
        return '—';
    }

    return date('F j, Y g:i A', $timestamp);
}

function getStrandLabel(?string $strand): string
{
    if ($strand === null || $strand === '') {
        return '—';
    }

    global $pdo;
    if ($pdo) {
        try {
            $stmt = $pdo->prepare('SELECT name FROM academic_programs WHERE code = :code LIMIT 1');
            $stmt->execute(['code' => strtolower($strand)]);
            $name = $stmt->fetchColumn();
            if ($name) {
                return $name;
            }
        } catch (PDOException $e) {
            // Fallback to basic capitalization if DB fails
        }
    }

    return strtoupper($strand);
}

/**
 * Logs an activity to the activity_logs table.
 *
 * @param int $userId
 * @param string $icon
 * @param string $title
 * @param string $description
 * @return void
 */
function logActivity(
    int $userId, 
    string $icon, 
    string $title, 
    string $description,
    ?string $affectedRecord = null,
    ?array $oldValue = null,
    ?array $newValue = null,
    ?string $reason = null
): void {
    global $pdo;
    
    if (!$pdo) {
        error_log('logActivity failed: PDO connection is not available in the global scope.');
        return;
    }

    $ipAddress = $_SERVER['REMOTE_ADDR'] ?? null;
    $oldValJson = $oldValue ? json_encode($oldValue) : null;
    $newValJson = $newValue ? json_encode($newValue) : null;

    try {
        $stmt = $pdo->prepare('
            INSERT INTO activity_logs 
            (user_id, ip_address, affected_record, icon, title, description, old_value, new_value, reason) 
            VALUES 
            (:user_id, :ip_address, :affected_record, :icon, :title, :description, :old_value, :new_value, :reason)
        ');
        $stmt->execute([
            'user_id' => $userId,
            'ip_address' => $ipAddress,
            'affected_record' => $affectedRecord,
            'icon' => $icon,
            'title' => $title,
            'description' => $description,
            'old_value' => $oldValJson,
            'new_value' => $newValJson,
            'reason' => $reason
        ]);
    } catch (PDOException $e) {
        error_log('logActivity failed: ' . $e->getMessage());
    }
}

/**
 * Retrieves milestones timestamps from activity logs.
 *
 * @param int $userId
 * @return array<string, string|null>
 */
function getApplicationTimestamps(int $userId): array
{
    global $pdo;
    $timestamps = [
        'created' => null,
        'submitted' => null,
        'documents' => null,
        'review' => null,
        'correction' => null,
        'approved' => null,
        'enrolled' => null,
    ];

    if (!$pdo) {
        return $timestamps;
    }

    try {
        // Fetch user registration date
        $uStmt = $pdo->prepare('SELECT created_at FROM users WHERE id = :id LIMIT 1');
        $uStmt->execute(['id' => $userId]);
        $created = $uStmt->fetchColumn();
        if ($created) {
            $timestamps['created'] = $created;
        }

        // Fetch activity logs ordered chronologically
        $stmt = $pdo->prepare('SELECT title, created_at FROM activity_logs WHERE user_id = :user_id ORDER BY created_at ASC');
        $stmt->execute(['user_id' => $userId]);
        $logs = $stmt->fetchAll();

        foreach ($logs as $log) {
            $title = $log['title'];
            $date = $log['created_at'];

            if (stripos($title, 'Portal Access Granted') !== false) {
                $timestamps['created'] = $date;
            } elseif (stripos($title, 'Application Submitted') !== false) {
                $timestamps['submitted'] = $date;
            } elseif (stripos($title, 'Document Uploaded') !== false || stripos($title, 'Submission Workflow Updated') !== false) {
                $timestamps['documents'] = $date;
            } elseif (stripos($title, 'Application Status: Under Review') !== false) {
                $timestamps['review'] = $date;
            } elseif (stripos($title, 'Application Status: Correction Required') !== false) {
                $timestamps['correction'] = $date;
            } elseif (stripos($title, 'Application Status: Approved') !== false) {
                $timestamps['approved'] = $date;
            } elseif (stripos($title, 'Application Status: Enrolled') !== false || stripos($title, 'Application Status: Officially Enrolled') !== false) {
                $timestamps['enrolled'] = $date;
            }
        }

        // Fallbacks if timeline events are present but log is missing
        $appStmt = $pdo->prepare('SELECT status, created_at, updated_at FROM applications WHERE user_id = :user_id LIMIT 1');
        $appStmt->execute(['user_id' => $userId]);
        $app = $appStmt->fetch();

        if ($app) {
            if (!$timestamps['submitted']) {
                $timestamps['submitted'] = $app['created_at'];
            }
            if (in_array($app['status'], ['approved', 'enrolled'], true) && !$timestamps['approved']) {
                $timestamps['approved'] = $app['updated_at'];
            }
            if ($app['status'] === 'enrolled' && !$timestamps['enrolled']) {
                $timestamps['enrolled'] = $app['updated_at'];
            }
        }

    } catch (PDOException $e) {
        error_log('getApplicationTimestamps failed: ' . $e->getMessage());
    }

    return $timestamps;
}

/**
 * Returns document requirements with detailed status mapping.
 *
 * @param int $appId
 * @return array
 */
function getDetailedChecklist(int $appId): array
{
    global $pdo;
    
    $required = [
        'PSA Birth Certificate' => 'Clear scanned copy of PSA Birth Certificate',
        'Form 138' => 'Report Card / Form 138 from previous school year',
        'Good Moral Certificate' => 'Certificate of Good Moral Character',
        '2x2 Picture' => 'Recent 2x2 ID picture with white background'
    ];

    $checklist = [];
    
    foreach ($required as $name => $desc) {
        $checklist[$name] = [
            'name' => $name,
            'desc' => $desc,
            'status' => 'Pending', // Pending, Uploaded, Verified, Needs Reupload
            'file_path' => null,
            'id' => null,
            'feedback' => null
        ];
    }

    if (!$pdo || $appId <= 0) {
        return $checklist;
    }

    try {
        $stmt = $pdo->prepare('SELECT id, document_name, status, file_path, feedback FROM application_documents WHERE application_id = :app_id');
        $stmt->execute(['app_id' => $appId]);
        $docs = $stmt->fetchAll();

        foreach ($docs as $doc) {
            $name = $doc['document_name'];
            if (isset($checklist[$name])) {
                $dbStatus = $doc['status'];
                $mappedStatus = 'Pending';
                
                if ($dbStatus === 'pending') {
                    $mappedStatus = 'Uploaded';
                } elseif ($dbStatus === 'verified') {
                    $mappedStatus = 'Verified';
                } elseif ($dbStatus === 'rejected') {
                    $mappedStatus = 'Needs Reupload';
                }
                
                $checklist[$name]['status'] = $mappedStatus;
                $checklist[$name]['file_path'] = $doc['file_path'];
                $checklist[$name]['id'] = (int)$doc['id'];
                $checklist[$name]['feedback'] = $doc['feedback'] ?? null;
            }
        }
    } catch (PDOException $e) {
        error_log('getDetailedChecklist failed: ' . $e->getMessage());
    }

    return $checklist;
}

/**
 * Generates a unique student number in the format YYYY-XXXXXX
 * Example: 2026-000001
 * 
 * @param PDO $pdo
 * @return string
 */
function generateStudentNumber(PDO $pdo): string
{
    $currentYear = date('Y');
    $prefix = $currentYear . '-';

    try {
        $stmt = $pdo->prepare('SELECT student_number FROM users WHERE student_number LIKE :prefix ORDER BY student_number DESC LIMIT 1');
        $stmt->execute(['prefix' => $prefix . '%']);
        $lastNumber = $stmt->fetchColumn();

        if ($lastNumber) {
            $lastSequence = (int) substr($lastNumber, 5);
            $newSequence = $lastSequence + 1;
        } else {
            $newSequence = 1;
        }

        return $prefix . str_pad((string)$newSequence, 6, '0', STR_PAD_LEFT);
    } catch (PDOException $e) {
        error_log('generateStudentNumber failed: ' . $e->getMessage());
        return $prefix . '000000'; // fallback, though it may cause unique constraint violation
    }
}

/**
 * Finalizes enrollment for an applicant once payment is confirmed.
 * Sets application status to 'enrolled' and assigns an official student number.
 */
function finalizeStudentEnrollment(PDO $pdo, int $userId, int $applicationId): void
{
    // 1. Update Application status to 'enrolled'
    $updApp = $pdo->prepare('UPDATE applications SET status = "enrolled" WHERE id = :id');
    $updApp->execute(['id' => $applicationId]);

    // 2. Generate and assign Student Number if empty
    $uStmt = $pdo->prepare('SELECT student_number, first_name, last_name, email FROM users WHERE id = :id LIMIT 1');
    $uStmt->execute(['id' => $userId]);
    $userRow = $uStmt->fetch(PDO::FETCH_ASSOC);

    if (!$userRow) return;

    $studentNumber = $userRow['student_number'] ?? '';
    if (empty($studentNumber)) {
        $studentNumber = generateStudentNumber($pdo);
        $updUser = $pdo->prepare('UPDATE users SET student_number = :student_number WHERE id = :id');
        $updUser->execute(['student_number' => $studentNumber, 'id' => $userId]);

        // Activity log
        $logStmt = $pdo->prepare('INSERT INTO activity_logs (user_id, icon, title, description) VALUES (:user_id, "bi-person-vcard-fill text-success", "Student Number Assigned", :description)');
        $logStmt->execute([
            'user_id' => $userId,
            'description' => "Your official student number is {$studentNumber}."
        ]);
    }

    // 3. Activity log for Enrollment Complete
    $logEnrolled = $pdo->prepare('INSERT INTO activity_logs (user_id, icon, title, description) VALUES (:user_id, "bi-patch-check-fill text-success", "Enrollment Complete", "Congratulations! You are officially enrolled as a student at Triple T University.")');
    $logEnrolled->execute(['user_id' => $userId]);
}

/**
 * Retrieves a single system setting value by key.
 */
function getSystemSetting(PDO $pdo, string $key, $default = null)
{
    try {
        $stmt = $pdo->prepare('SELECT setting_value FROM system_settings WHERE setting_key = :key LIMIT 1');
        $stmt->execute(['key' => $key]);
        $val = $stmt->fetchColumn();
        return $val !== false ? $val : $default;
    } catch (PDOException $e) {
        error_log('getSystemSetting failed for key ' . $key . ': ' . $e->getMessage());
        return $default;
    }
}

/**
 * Retrieves multiple system settings by an array of keys.
 * Returns an associative array of [key => value].
 */
function getSystemSettings(PDO $pdo, array $keys): array
{
    if (empty($keys)) return [];
    try {
        $placeholders = implode(',', array_fill(0, count($keys), '?'));
        $stmt = $pdo->prepare("SELECT setting_key, setting_value FROM system_settings WHERE setting_key IN ($placeholders)");
        $stmt->execute($keys);
        $results = $stmt->fetchAll(PDO::FETCH_KEY_PAIR);
        return $results ?: [];
    } catch (PDOException $e) {
        error_log('getSystemSettings failed: ' . $e->getMessage());
        return [];
    }
}

const ROLE_PERMISSIONS = [
    'superadmin' => ['*'],
    'admin' => [
        'students.view', 'students.edit',
        'programs.manage', 'subjects.manage', 
        'curriculum.manage', 'shs_curriculum.manage', 'college_curriculum.manage', 
        'enrollment.finalize',
        'applications.view_details'
    ],
    'scheduler' => [
        'sections.manage', 'shs_sections.manage', 'college_sections.manage',
        'schedules.manage'
    ],
    'admissions' => [
        'applications.view_queue', 'applications.view_details',
        'applications.review', 'documents.verify'
    ],
    'cashier' => [
        'fees.manage', 'assessments.generate', 
        'payments.record', 'receipts.print'
    ],
    'scholarship' => [
        'scholarships.manage', 
        'scholarship_applications.review' 
    ],
    'clinic' => [
        'medical.review'
    ]
];

function hasPermission(string|array $permission): bool
{
    $userRole = $_SESSION['user_role'] ?? '';
    if (empty($userRole)) {
        return false;
    }

    $basePermissions = ROLE_PERMISSIONS[$userRole] ?? [];
    $customPermissions = $_SESSION['user_permissions'] ?? [];
    $userPermissions = array_merge($basePermissions, $customPermissions);
    
    if (in_array('*', $userPermissions, true)) {
        return true;
    }

    $checkPermissions = is_array($permission) ? $permission : [$permission];
    
    foreach ($checkPermissions as $perm) {
        if (in_array($perm, $userPermissions, true)) {
            return true;
        }
    }
    
    return false;
}

/**
 * Generates an HTML hidden input field containing the CSRF token.
 * 
 * @return string
 */
function getCsrfInput(): string
{
    $token = $_SESSION['csrf_token'] ?? '';
    return '<input type="hidden" name="csrf_token" value="' . htmlspecialchars($token, ENT_QUOTES, 'UTF-8') . '">';
}

/**
 * Enforces permission requirements. Throws 403 if user lacks permission.
 * 
 * @param string|array $permission
 * @throws \App\Core\HttpException
 */
function requirePermission(string|array $permission): void
{
    if (!hasPermission($permission)) {
        throw new \App\Core\HttpException(403, 'Access Denied. You do not have permission to access this module.');
    }
}

/**
 * Recalculates the student assessment based on active scholarship recipients.
 * @param int $userId
 * @param \PDO $pdo
 * @return void
 */
function recalculateStudentAssessment(int $userId, \PDO $pdo): void
{
    // Find active assessment for user
    $assStmt = $pdo->prepare('
        SELECT sa.* 
        FROM student_assessments sa
        JOIN applications a ON sa.application_id = a.id
        WHERE sa.user_id = :user_id 
          AND a.status IN ("approved", "enrolled")
        ORDER BY sa.created_at DESC LIMIT 1
    ');
    $assStmt->execute(['user_id' => $userId]);
    $assessment = $assStmt->fetch(\PDO::FETCH_ASSOC);

    if (!$assessment) return;

    $assessmentId = $assessment['id'];
    
    // Find active scholarships for user
    $sysStmt = $pdo->query("SELECT setting_key, setting_value FROM system_settings WHERE setting_key IN ('active_academic_year_id', 'active_semester')");
    $settings = [];
    foreach ($sysStmt->fetchAll() as $row) {
        $settings[$row['setting_key']] = $row['setting_value'];
    }

    $activeAy = $settings['active_academic_year_id'] ?? 0;
    $activeSem = $settings['active_semester'] ?? '';

    $scholStmt = $pdo->prepare('
        SELECT s.* 
        FROM scholarship_recipients sr
        JOIN scholarships s ON sr.scholarship_id = s.id
        WHERE sr.user_id = :uid AND sr.status = "Active"
          AND sr.academic_year_id = :ay AND sr.semester = :sem
    ');
    $scholStmt->execute([
        'uid' => $userId,
        'ay' => $activeAy,
        'sem' => $activeSem
    ]);
    
    $activeScholarships = $scholStmt->fetchAll(\PDO::FETCH_ASSOC);

    $totalTuitionDiscount = 0;
    $totalMiscDiscount = 0;

    $tuitionFee = (float)$assessment['tuition_fee'];
    $miscFee = (float)$assessment['miscellaneous_fee'] + (float)$assessment['registration_fee'] + (float)$assessment['laboratory_fee'] + (float)$assessment['other_fees'];

    foreach ($activeScholarships as $scholarship) {
        // Tuition discount
        if ($scholarship['tuition_coverage_type'] === 'full') {
            $totalTuitionDiscount = $tuitionFee;
        } elseif ($scholarship['tuition_coverage_type'] === 'percentage') {
            $percent = (float)$scholarship['tuition_coverage_value'];
            $totalTuitionDiscount += $tuitionFee * ($percent / 100);
        } else {
            $totalTuitionDiscount += (float)$scholarship['tuition_coverage_value'];
        }

        // Misc discount
        if ($scholarship['misc_coverage_type'] === 'full') {
            $totalMiscDiscount = $miscFee;
        } elseif ($scholarship['misc_coverage_type'] === 'percentage') {
            $percent = (float)$scholarship['misc_coverage_value'];
            $totalMiscDiscount += $miscFee * ($percent / 100);
        } else {
            $totalMiscDiscount += (float)$scholarship['misc_coverage_value'];
        }
    }

    if ($totalTuitionDiscount > $tuitionFee) $totalTuitionDiscount = $tuitionFee;
    if ($totalMiscDiscount > $miscFee) $totalMiscDiscount = $miscFee;

    $totalDiscount = $totalTuitionDiscount + $totalMiscDiscount;
    $totalAmount = (float)$assessment['total_amount'];
    
    $netAmount = $totalAmount - $totalDiscount;
    if ($netAmount < 0) $netAmount = 0;
    
    $totalPaid = (float)$assessment['total_paid'];
    $paymentStatus = 'unpaid';
    if ($totalPaid >= $netAmount && $netAmount > 0) {
        $paymentStatus = 'paid';
    } elseif ($totalPaid > 0) {
        $paymentStatus = 'partial';
    } elseif ($netAmount == 0) {
        $paymentStatus = 'paid';
    }

    $updStmt = $pdo->prepare('
        UPDATE student_assessments 
        SET discount_amount = :discount, net_amount = :net, payment_status = :status 
        WHERE id = :id
    ');
    $updStmt->execute([
        'discount' => $totalDiscount,
        'net' => $netAmount,
        'status' => $paymentStatus,
        'id' => $assessmentId
    ]);
}

/**
 * Sends a 6-digit email verification OTP to an applicant.
 * 
 * @param string $recipientEmail
 * @param string $recipientName
 * @param string $code
 * @param string|null &$errorMessage
 * @return bool
 */
function sendVerificationCodeEmail(string $recipientEmail, string $recipientName, string $code, ?string &$errorMessage = null): bool
{
    $recipientEmail = trim($recipientEmail);
    if (!filter_var($recipientEmail, FILTER_VALIDATE_EMAIL)) {
        $errorMessage = "Invalid recipient email address format: {$recipientEmail}";
        error_log($errorMessage);
        return false;
    }

    if (!class_exists('\PHPMailer\PHPMailer\PHPMailer')) {
        $autoloadPath = __DIR__ . '/../../vendor/autoload.php';
        if (file_exists($autoloadPath)) {
            require_once $autoloadPath;
        }
    }

    if (!class_exists('\PHPMailer\PHPMailer\PHPMailer')) {
        $errorMessage = "PHPMailer library is not available.";
        error_log("{$errorMessage} Verification code for {$recipientEmail}: {$code}");
        return false;
    }

    // Ensure .env is loaded if not already in environment
    if (!getenv('SMTP_USERNAME')) {
        $envFile = __DIR__ . '/../../.env';
        if (file_exists($envFile)) {
            $lines = file($envFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
            foreach ($lines as $line) {
                if (strpos(trim($line), '#') === 0) continue;
                if (strpos($line, '=') === false) continue;
                list($name, $value) = explode('=', $line, 2);
                $name = trim($name);
                $value = trim(trim($value), '"\'');
                putenv(sprintf('%s=%s', $name, $value));
                $_ENV[$name] = $value;
                $_SERVER[$name] = $value;
            }
        }
    }

    try {
        $mail = new \PHPMailer\PHPMailer\PHPMailer(true);
        $mail->isSMTP();
        $mail->Host       = getenv('SMTP_HOST') ?: 'smtp.gmail.com';
        $mail->SMTPAuth   = true;
        $mail->Username   = getenv('SMTP_USERNAME') ?: '';
        $mail->Password   = getenv('SMTP_PASSWORD') ?: '';

        $enc = getenv('SMTP_ENCRYPTION') ?: 'tls';
        if (strtolower((string)$enc) === 'ssl') {
            $mail->SMTPSecure = \PHPMailer\PHPMailer\PHPMailer::ENCRYPTION_SMTPS;
        } else {
            $mail->SMTPSecure = \PHPMailer\PHPMailer\PHPMailer::ENCRYPTION_STARTTLS;
        }

        $mail->Port = (int)(getenv('SMTP_PORT') ?: 587);
        $mail->Timeout = 10;

        $mail->SMTPOptions = [
            'ssl' => [
                'verify_peer' => false,
                'verify_peer_name' => false,
                'allow_self_signed' => true
            ]
        ];

        $fromAddress = getenv('MAIL_FROM_ADDRESS') ?: 'no-reply@ttu.edu.ph';
        $fromName = getenv('MAIL_FROM_NAME') ?: 'Triple T University';
        $mail->setFrom($fromAddress, $fromName);
        $mail->addAddress($recipientEmail, $recipientName);

        $logoPath = __DIR__ . '/../../public/images/TTU_LOGO.png';
        if (!file_exists($logoPath)) {
            $logoPath = __DIR__ . '/../../images/TTU_LOGO.png';
        }
        if (file_exists($logoPath)) {
            $mail->addEmbeddedImage($logoPath, 'ttu_logo');
        }

        $campusPath = __DIR__ . '/../../images/ttu_campus.jpg';
        if (!file_exists($campusPath)) {
            $campusPath = __DIR__ . '/../../public/images/ttu_campus.jpg';
        }
        if (file_exists($campusPath)) {
            $mail->addEmbeddedImage($campusPath, 'ttu_campus');
        }

        $mail->isHTML(true);
        $mail->Subject = 'Verify Your Email Address - Triple T University';

        ob_start();
        $firstName = $recipientName;
        require __DIR__ . '/../Views/emails/email_verification.php';
        $mail->Body = ob_get_clean();

        $sent = $mail->send();
        if ($sent) {
            return true;
        }

        $errorMessage = $mail->ErrorInfo ?: 'Unknown mailer error.';
        return false;
    } catch (\Throwable $e) {
        $errorMessage = $e->getMessage();
        error_log('Verification Mailer Error: ' . $errorMessage . " | Code was: {$code}");
        return false;
    }
}

/**
 * Sends the official Student & LMS Credentials email to an enrolled student.
 */
function sendStudentCredentialsEmail(string $recipientEmail, string $firstName, string $ttuEmail, string $studentNumber, string $tempPassword, ?string &$errorMessage = null): bool
{
    $recipientEmail = trim($recipientEmail);
    if (!filter_var($recipientEmail, FILTER_VALIDATE_EMAIL)) {
        $errorMessage = "Invalid recipient email address format: {$recipientEmail}";
        error_log($errorMessage);
        return false;
    }

    if (!class_exists('\PHPMailer\PHPMailer\PHPMailer')) {
        $autoloadPath = __DIR__ . '/../../vendor/autoload.php';
        if (file_exists($autoloadPath)) {
            require_once $autoloadPath;
        }
    }

    if (!class_exists('\PHPMailer\PHPMailer\PHPMailer')) {
        $errorMessage = "PHPMailer library is not available.";
        error_log($errorMessage);
        return false;
    }

    // Ensure .env is loaded
    if (!getenv('SMTP_USERNAME')) {
        $envFile = __DIR__ . '/../../.env';
        if (file_exists($envFile)) {
            $lines = file($envFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
            foreach ($lines as $line) {
                if (strpos(trim($line), '#') === 0) continue;
                if (strpos($line, '=') === false) continue;
                list($name, $value) = explode('=', $line, 2);
                $name = trim($name);
                $value = trim(trim($value), '"\'');
                putenv(sprintf('%s=%s', $name, $value));
                $_ENV[$name] = $value;
                $_SERVER[$name] = $value;
            }
        }
    }

    try {
        $mail = new \PHPMailer\PHPMailer\PHPMailer(true);
        $mail->isSMTP();
        $mail->Host       = getenv('SMTP_HOST') ?: 'smtp.gmail.com';
        $mail->SMTPAuth   = true;
        $mail->Username   = getenv('SMTP_USERNAME') ?: '';
        $mail->Password   = getenv('SMTP_PASSWORD') ?: '';

        $enc = getenv('SMTP_ENCRYPTION') ?: 'tls';
        if (strtolower((string)$enc) === 'ssl') {
            $mail->SMTPSecure = \PHPMailer\PHPMailer\PHPMailer::ENCRYPTION_SMTPS;
        } else {
            $mail->SMTPSecure = \PHPMailer\PHPMailer\PHPMailer::ENCRYPTION_STARTTLS;
        }

        $mail->Port = (int)(getenv('SMTP_PORT') ?: 587);
        $mail->Timeout = 10;

        $mail->SMTPOptions = [
            'ssl' => [
                'verify_peer' => false,
                'verify_peer_name' => false,
                'allow_self_signed' => true
            ]
        ];

        $fromAddress = getenv('MAIL_FROM_ADDRESS') ?: 'no-reply@ttu.edu.ph';
        $fromName = getenv('MAIL_FROM_NAME') ?: 'Triple T University';
        $mail->setFrom($fromAddress, $fromName);
        $mail->addAddress($recipientEmail, $firstName);

        $logoPath = __DIR__ . '/../../public/images/TTU_LOGO.png';
        if (!file_exists($logoPath)) {
            $logoPath = __DIR__ . '/../../images/TTU_LOGO.png';
        }
        if (file_exists($logoPath)) {
            $mail->addEmbeddedImage($logoPath, 'ttu_logo');
        }

        $campusPath = __DIR__ . '/../../images/ttu_campus.jpg';
        if (!file_exists($campusPath)) {
            $campusPath = __DIR__ . '/../../public/images/ttu_campus.jpg';
        }
        if (file_exists($campusPath)) {
            $mail->addEmbeddedImage($campusPath, 'ttu_campus');
        }

        $mail->isHTML(true);
        $mail->Subject = 'Welcome to Triple T University - Official Student & LMS Credentials';

        $portalLink = 'http://localhost/sia/public/index.php';
        ob_start();
        require __DIR__ . '/../Views/emails/welcome_credentials.php';
        $mail->Body = ob_get_clean();

        $sent = $mail->send();
        if ($sent) {
            return true;
        }

        $errorMessage = $mail->ErrorInfo ?: 'Unknown mailer error.';
        return false;
    } catch (\Throwable $e) {
        $errorMessage = $e->getMessage();
        error_log('Credentials Mailer Error: ' . $errorMessage);
        return false;
    }
}

/**
 * Sends a 6-digit Password Reset OTP email.
 * 
 * @param string $recipientEmail
 * @param string $recipientName
 * @param string $code
 * @param string $portalType 'applicant' or 'faculty'
 * @param string|null $resetUrl
 * @param string|null &$errorMessage
 * @return bool
 */
function sendPasswordResetOtpEmail(
    string $recipientEmail,
    string $recipientName,
    string $code,
    string $portalType = 'applicant',
    ?string $resetUrl = null,
    ?string &$errorMessage = null
): bool {
    $recipientEmail = trim($recipientEmail);
    if (!filter_var($recipientEmail, FILTER_VALIDATE_EMAIL)) {
        $errorMessage = "Invalid recipient email address format: {$recipientEmail}";
        error_log($errorMessage);
        return false;
    }

    if (!class_exists('\PHPMailer\PHPMailer\PHPMailer')) {
        $autoloadPath = __DIR__ . '/../../vendor/autoload.php';
        if (file_exists($autoloadPath)) {
            require_once $autoloadPath;
        }
    }

    if (!class_exists('\PHPMailer\PHPMailer\PHPMailer')) {
        $errorMessage = "PHPMailer library is not available.";
        error_log("{$errorMessage} Password reset OTP for {$recipientEmail}: {$code}");
        return false;
    }

    // Ensure .env is loaded
    if (!getenv('SMTP_USERNAME')) {
        $envFile = __DIR__ . '/../../.env';
        if (file_exists($envFile)) {
            $lines = file($envFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
            foreach ($lines as $line) {
                if (strpos(trim($line), '#') === 0) continue;
                if (strpos($line, '=') === false) continue;
                list($name, $value) = explode('=', $line, 2);
                $name = trim($name);
                $value = trim(trim($value), '"\'');
                putenv(sprintf('%s=%s', $name, $value));
                $_ENV[$name] = $value;
                $_SERVER[$name] = $value;
            }
        }
    }

    try {
        $mail = new \PHPMailer\PHPMailer\PHPMailer(true);
        $mail->isSMTP();
        $mail->Host       = getenv('SMTP_HOST') ?: 'smtp.gmail.com';
        $mail->SMTPAuth   = true;
        $mail->Username   = getenv('SMTP_USERNAME') ?: '';
        $mail->Password   = getenv('SMTP_PASSWORD') ?: '';

        $enc = getenv('SMTP_ENCRYPTION') ?: 'tls';
        if (strtolower((string)$enc) === 'ssl') {
            $mail->SMTPSecure = \PHPMailer\PHPMailer\PHPMailer::ENCRYPTION_SMTPS;
        } else {
            $mail->SMTPSecure = \PHPMailer\PHPMailer\PHPMailer::ENCRYPTION_STARTTLS;
        }

        $mail->Port = (int)(getenv('SMTP_PORT') ?: 587);
        $mail->Timeout = 10;

        $mail->SMTPOptions = [
            'ssl' => [
                'verify_peer' => false,
                'verify_peer_name' => false,
                'allow_self_signed' => true
            ]
        ];

        $fromAddress = getenv('MAIL_FROM_ADDRESS') ?: 'no-reply@ttu.edu.ph';
        $fromName = getenv('MAIL_FROM_NAME') ?: 'Triple T University';
        $mail->setFrom($fromAddress, $fromName);
        $mail->addAddress($recipientEmail, $recipientName);

        $logoPath = __DIR__ . '/../../public/images/TTU_LOGO.png';
        if (!file_exists($logoPath)) {
            $logoPath = __DIR__ . '/../../images/TTU_LOGO.png';
        }
        if (file_exists($logoPath)) {
            $mail->addEmbeddedImage($logoPath, 'ttu_logo');
        }

        $campusPath = __DIR__ . '/../../images/ttu_campus.jpg';
        if (!file_exists($campusPath)) {
            $campusPath = __DIR__ . '/../../public/images/ttu_campus.jpg';
        }
        if (file_exists($campusPath)) {
            $mail->addEmbeddedImage($campusPath, 'ttu_campus');
        }

        $mail->isHTML(true);
        $portalLabel = ($portalType === 'faculty') ? 'Faculty LMS Portal' : 'Applicant Account';
        $mail->Subject = "Password Reset Code ({$code}) - {$portalLabel} | Triple T University";

        ob_start();
        require __DIR__ . '/../Views/emails/password_reset_otp.php';
        $mail->Body = ob_get_clean();

        $sent = $mail->send();
        if ($sent) {
            return true;
        }

        $errorMessage = $mail->ErrorInfo ?: 'Unknown mailer error.';
        return false;
    } catch (\Throwable $e) {
        $errorMessage = $e->getMessage();
        error_log('Password Reset Mailer Error: ' . $errorMessage . " | Code was: {$code}");
        return false;
    }
}
