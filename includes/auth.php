<?php

declare(strict_types=1);

if (session_status() === PHP_SESSION_NONE) {
    session_set_cookie_params([
        'lifetime' => 0,
        'path' => '/',
        'domain' => '',
        'secure' => isset($_SERVER['HTTPS']),
        'httponly' => true,
        'samesite' => 'Strict'
    ]);
    session_start();
}

// Session Hijacking and Fixation Protection
if (!empty($_SESSION['logged_in'])) {
    $currentIp = $_SERVER['REMOTE_ADDR'] ?? '';
    $userAgent = $_SERVER['HTTP_USER_AGENT'] ?? '';

    if (!isset($_SESSION['user_ip']) || !isset($_SESSION['user_agent'])) {
        $_SESSION['user_ip'] = $currentIp;
        $_SESSION['user_agent'] = $userAgent;
    } elseif ($_SESSION['user_ip'] !== $currentIp || $_SESSION['user_agent'] !== $userAgent) {
        // Potential hijacking detected
        session_unset();
        session_destroy();
        showErrorPage(
            'Session Validation Failed',
            'Your session parameters have changed. For security reasons, please log in again.',
            401
        );
    }

    // Periodic Session Regeneration
    if (!isset($_SESSION['created_time'])) {
        $_SESSION['created_time'] = time();
    } elseif (time() - $_SESSION['created_time'] > 1800) { // 30 minutes
        session_regenerate_id(true);
        $_SESSION['created_time'] = time();
    }
}

// Generate CSRF Token
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

function getCsrfInput(): string
{
    $token = $_SESSION['csrf_token'] ?? '';
    return '<input type="hidden" name="csrf_token" value="' . htmlspecialchars($token, ENT_QUOTES, 'UTF-8') . '">';
}

function verifyCsrfToken(): void
{
    $token = $_POST['csrf_token'] ?? '';
    if (empty($_SESSION['csrf_token']) || !hash_equals($_SESSION['csrf_token'], $token)) {
        showErrorPage(
            'Security Verification Failed',
            'Invalid CSRF security token. Please try refreshing and submitting the form again.',
            403
        );
    }
}

function showErrorPage(string $title, string $message, int $statusCode = 400): void
{
    if (!headers_sent()) {
        http_response_code($statusCode);
    }
    
    // Log the error internally
    error_log("Security/ValidationError: [{$statusCode}] {$title} - {$message}");
    
    // Check if it's an AJAX/API request (JSON response helper)
    if (
        (isset($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest') ||
        (isset($_SERVER['CONTENT_TYPE']) && str_contains(strtolower($_SERVER['CONTENT_TYPE']), 'application/json'))
    ) {
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode(['error' => $message]);
        exit;
    }
    ?>
    <!DOCTYPE html>
    <html lang="en">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title><?= htmlspecialchars($title, ENT_QUOTES, 'UTF-8') ?></title>
        <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css">
        <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">
        <style>
            body { background-color: #f8f9fa; font-family: system-ui, -apple-system, sans-serif; display: flex; align-items: center; justify-content: center; min-height: 100vh; }
            .error-card { max-width: 500px; width: 100%; border: 0; border-radius: 16px; box-shadow: 0 10px 30px rgba(0,0,0,0.05); }
        </style>
    </head>
    <body>
        <div class="card error-card p-4 text-center">
            <div class="text-danger mb-3" style="font-size: 3rem;">
                <i class="bi bi-shield-slash text-danger"></i>
            </div>
            <h2 class="h4 fw-bold text-dark mb-2"><?= htmlspecialchars($title, ENT_QUOTES, 'UTF-8') ?></h2>
            <p class="text-muted mb-4"><?= htmlspecialchars($message, ENT_QUOTES, 'UTF-8') ?></p>
            <a href="javascript:history.back()" class="btn btn-primary rounded-pill px-4">
                <i class="bi bi-arrow-left me-1"></i> Go Back
            </a>
        </div>
    </body>
    </html>
    <?php
    exit;
}

const ROLE_PERMISSIONS = [
    'superadmin' => ['*'],
    
    'admin' => [
        'students.view', 'students.edit',
        'programs.manage', 'subjects.manage', 
        'curriculum.manage', 'shs_curriculum.manage', 'college_curriculum.manage', 
        'sections.manage', 'shs_sections.manage', 'college_sections.manage',
        'schedules.manage', 'enrollment.finalize',
        'applications.view_details'
    ],
    
    'admissions' => [
        'applications.view_queue', 'applications.view_details',
        'applications.review', 'documents.verify', 
        'medical.review'
    ],
    
    'cashier' => [
        'fees.manage', 'assessments.generate', 
        'payments.record', 'receipts.print'
    ],
    
    'scholarship' => [
        'scholarships.manage', 
        'scholarship_applications.review' 
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

function requirePermission(string|array $permission): void
{
    if (empty($_SESSION['logged_in'])) {
        header('Location: /sia/auth/login.php');
        exit;
    }

    if (!hasPermission($permission)) {
        $userRole = $_SESSION['user_role'] ?? '';
        if ($userRole === 'applicant') {
            header('Location: /sia/applicant/dashboard.php');
        } else {
            $_SESSION['admin_error'] = 'Access denied. You do not have permission to perform this action or view this module.';
            
            if ($userRole === 'admissions') {
                header('Location: /sia/admin/dashboard.php');
            } elseif ($userRole === 'admin') {
                header('Location: /sia/admin/dashboard.php');
            } elseif ($userRole === 'scholarship') {
                header('Location: /sia/admin/scholarship_review.php');
            } elseif ($userRole === 'cashier') {
                header('Location: /sia/admin/cashier_dashboard.php');
            } else {
                header('Location: /sia/admin/dashboard.php');
            }
        }
        exit;
    }
}

function requireApplicantLogin(): void
{
    if (empty($_SESSION['logged_in']) || ($_SESSION['user_role'] ?? '') !== 'applicant') {
        header('Location: ../auth/login.php');
        exit;
    }
}

function requireRole(array $allowedRoles): void
{
    if (empty($_SESSION['logged_in'])) {
        header('Location: /sia/auth/login.php');
        exit;
    }
    
    $userRole = $_SESSION['user_role'] ?? '';
    
    if (!in_array($userRole, $allowedRoles, true)) {
        if ($userRole === 'applicant') {
            header('Location: /sia/applicant/dashboard.php');
        } else {
            // Unauthorized admin role trying to access another admin module
            $_SESSION['admin_error'] = 'Access denied. You do not have permission to view this module.';
            
            // Redirect to a safe fallback admin page depending on their actual role
            if ($userRole === 'admissions') {
                header('Location: /sia/admin/dashboard.php');
            } elseif ($userRole === 'admin') {
                header('Location: /sia/admin/students.php');
            } elseif ($userRole === 'scholarship') {
                header('Location: /sia/admin/scholarship_review.php');
            } elseif ($userRole === 'cashier') {
                header('Location: /sia/admin/cashier_dashboard.php');
            } else {
                header('Location: /sia/admin/dashboard.php');
            }
        }
        exit;
    }
}

function requireAdminLogin(): void
{
    // Backwards compatibility or generic admin check
    requireRole(['superadmin', 'admin', 'admissions', 'scholarship', 'cashier']);
}

function isPasswordStrong(string $password, array &$errors = []): bool
{
    if (strlen($password) < 8) {
        $errors[] = 'Password must be at least 8 characters long.';
    }
    if (!preg_match('/[A-Z]/', $password)) {
        $errors[] = 'Password must contain at least one uppercase letter.';
    }
    if (!preg_match('/[a-z]/', $password)) {
        $errors[] = 'Password must contain at least one lowercase letter.';
    }
    if (!preg_match('/[0-9]/', $password)) {
        $errors[] = 'Password must contain at least one number.';
    }
    if (!preg_match('/[^A-Za-z0-9]/', $password)) {
        $errors[] = 'Password must contain at least one special character (e.g. @, #, $, %, etc.).';
    }
    return empty($errors);
}
