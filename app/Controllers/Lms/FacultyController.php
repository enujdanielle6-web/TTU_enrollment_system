<?php
namespace App\Controllers\Lms;

use App\Core\BaseController;
use App\Core\Request;
use App\Core\Response;
use App\Core\Database;
use PDO;

class FacultyController extends BaseController
{
    public function dashboard(Request $request, Response $response)
    {
        $pdo = Database::getConnection();
        
    $response->redirect("/sia/lms/../../auth/lms_faculty_login.php");
    return;
}

$pageTitle = 'Faculty LMS Dashboard';
require_once __DIR__ . '/../../components/header.php';

        return $this->render('lms/faculty/dashboard', get_defined_vars());
    }
}



