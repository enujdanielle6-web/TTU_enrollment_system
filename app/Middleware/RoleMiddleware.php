<?php

namespace App\Middleware;

use App\Core\Request;
use App\Core\Response;
use Closure;

class RoleMiddleware implements MiddlewareInterface
{
    // These match the legacy include/auth.php definitions
    private const ROLE_PERMISSIONS = [
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

    /** @var string[] */
    private array $allowedRoles = [];

    /**
     * @param string[] $allowedRoles e.g. ['admin', 'superadmin']
     */
    public function __construct(array $allowedRoles = [])
    {
        $this->allowedRoles = $allowedRoles;
    }

    public function handle(Request $request, Closure $next)
    {
        if (empty($_SESSION['logged_in'])) {
            $response = new Response();
            $response->redirect('/sia/auth/login.php');
            exit;
        }

        $userRole = $_SESSION['user_role'] ?? '';

        // If specific roles were provided to the middleware constructor (not dynamically currently supported by our router easily, 
        // we need to check how to pass params to middleware in Router. Let's assume we can set properties or extend classes for specific roles for now,
        // or check against permissions).
        // Actually, for now, we just enforce that it's NOT an applicant if it's an admin route.
        // The implementation plan says "Accepts role parameters", but our router pipeline just instantiates: `new $middlewareClass()`.
        // So we can't easily pass arguments to the constructor.
        // For Phase 2, we just need basic role checking if needed, but AuthController doesn't strictly need RoleMiddleware for login/logout!
        // We will just define a generic `AdminMiddleware` or similar if needed, or handle it via a setter if we update the router later.

        $adminRoles = ['superadmin', 'admin', 'admissions', 'scholarship', 'cashier', 'clinic'];

        if (!empty($this->allowedRoles)) {
            if (in_array('admin', $this->allowedRoles, true)) {
                // 'admin' param in route group means ANY admin role is allowed
                if (!in_array($userRole, $adminRoles, true)) {
                    $this->redirectUnauthorized($userRole);
                }
            } else {
                // specific roles
                if (!in_array($userRole, $this->allowedRoles, true) && $userRole !== 'superadmin') {
                    $this->redirectUnauthorized($userRole);
                }
            }
        }

        return $next($request);
    }

    private function redirectUnauthorized(string $userRole): void
    {
        $response = new Response();
        
        if ($userRole === 'applicant') {
            $response->redirect('/sia/applicant/dashboard.php');
        } else {
            $_SESSION['admin_error'] = 'Access denied. You do not have permission to view this module.';
            
            if ($userRole === 'admissions') {
                $response->redirect('/sia/admin/admissions/admissions_dashboard.php');
            } elseif ($userRole === 'admin') {
                $response->redirect('/sia/admin/registrar/students.php');
            } elseif ($userRole === 'scholarship') {
                $response->redirect('/sia/admin/scholarship/scholarship_dashboard.php');
            } elseif ($userRole === 'cashier') {
                $response->redirect('/sia/admin/finance/cashier_dashboard.php');
            } else {
                $response->redirect('/sia/admin/dashboard.php');
            }
        }
        exit;
    }
}
