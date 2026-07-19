<?php

declare(strict_types=1);

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
            'label' => $docMethod === 'on_campus' ? 'On-Campus Verification' : 'Documents Uploaded',
            'description' => $docMethod === 'on_campus' ? 'Physical verification scheduled at the admissions office.' : 'Required academic documents uploaded (Verification pending).',
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
