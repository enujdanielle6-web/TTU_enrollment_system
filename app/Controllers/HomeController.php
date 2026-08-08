<?php

namespace App\Controllers;

use App\Core\BaseController;
use App\Core\Request;
use App\Core\Response;

class HomeController extends BaseController
{
    public function index(Request $request, Response $response)
    {
        return $this->render('home');
    }
}


