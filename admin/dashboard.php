<?php

declare(strict_types=1);

require_once __DIR__ . '/../includes/auth.php';

// Dashboard Router
// Redirects the user to their department's specific dashboard based on their highest permission.

if (hasPermission(['users.manage', 'settings.manage'])) {
    header('Location: system/sysadmin_dashboard.php');
    exit;
} elseif (hasPermission(['students.view', 'programs.manage'])) {
    header('Location: registrar/registrar_dashboard.php');
    exit;
} elseif (hasPermission('applications.view_queue')) {
    header('Location: admissions/admissions_dashboard.php');
    exit;
} elseif (hasPermission('assessments.generate')) {
    header('Location: finance/cashier_dashboard.php');
    exit;
} elseif (hasPermission('scholarships.manage')) {
    header('Location: scholarship/scholarship_dashboard.php');
    exit;
} else {
    // Fallback if the user has no dashboard access but somehow logged into admin
    header('Location: ../auth/login.php');
    exit;
}
