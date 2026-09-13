<?php

namespace App\Controllers;

use App\Core\BaseController;
use App\Core\Request;
use App\Core\Response;
use App\Core\Database;
use PDO;

class HomeController extends BaseController
{
    public function index(Request $request, Response $response)
    {
        $collegePrograms = [];
        $shsStrands = [];

        try {
            $pdo = Database::getConnection();

            // Fetch active SHS Strands with fee template info
            $shsStmt = $pdo->query("
                SELECT 
                    s.id,
                    s.code,
                    s.name,
                    s.description,
                    s.icon,
                    s.careers,
                    s.custom_tuition,
                    f.tuition_fee,
                    f.is_per_unit,
                    f.total_amount
                FROM shs_strands s
                LEFT JOIN fee_templates f ON f.strand = s.code AND f.academic_level = 'Senior High School'
                WHERE s.is_active = 1
                ORDER BY s.id ASC
            ");
            $shsStrands = $shsStmt->fetchAll(PDO::FETCH_ASSOC);

            // Fetch active College Programs with fee template info
            $collegeStmt = $pdo->query("
                SELECT 
                    p.id,
                    p.code,
                    p.name,
                    p.description,
                    p.icon,
                    p.careers,
                    p.custom_tuition,
                    f.tuition_fee,
                    f.is_per_unit,
                    f.total_amount
                FROM college_programs p
                LEFT JOIN fee_templates f ON f.strand = p.code AND f.academic_level = 'College'
                WHERE p.is_active = 1
                ORDER BY p.id ASC
            ");
            $collegePrograms = $collegeStmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (\Throwable $e) {
            error_log('HomeController index error: ' . $e->getMessage());
        }

        return $this->render('home', [
            'collegePrograms' => $collegePrograms,
            'shsStrands' => $shsStrands,
        ]);
    }

    public function demo(Request $request, Response $response)
    {
        return $this->render('demo_landing');
    }
}


