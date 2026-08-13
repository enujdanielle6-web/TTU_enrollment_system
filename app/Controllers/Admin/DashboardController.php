<?php
namespace App\Controllers\Admin;

use App\Core\BaseController;
use App\Core\Request;
use App\Core\Response;

class DashboardController extends BaseController
{
    public function index(Request $request, Response $response)
    {
        if (hasPermission(['users.manage', 'settings.manage'])) {
            $response->redirect('/sia/admin/system/sysadmin_dashboard.php');
            return;
        } elseif (hasPermission(['students.view', 'programs.manage'])) {
            $response->redirect('/sia/admin/registrar/registrar_dashboard.php');
            return;
        } elseif (hasPermission('applications.view_queue')) {
            $response->redirect('/sia/admin/admissions/admissions_dashboard.php');
            return;
        } elseif (hasPermission('assessments.generate')) {
            $response->redirect('/sia/admin/finance/cashier_dashboard.php');
            return;
        } elseif (hasPermission('scholarships.manage')) {
            $response->redirect('/sia/admin/scholarship/scholarship_dashboard.php');
            return;
        } elseif (hasPermission('medical.review')) {
            $response->redirect('/sia/admin/clinic/clinic_dashboard.php');
            return;
        } elseif (hasPermission('sections.manage') || hasPermission('shs_sections.manage')) {
            $response->redirect('/sia/admin/scheduler/scheduler_dashboard.php');
            return;
        } else {
            // Fallback if the user has no dashboard access but somehow logged into admin
            $response->redirect('/sia/auth/login.php');
            return;
        }
    }
}


